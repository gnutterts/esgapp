<?php

namespace App\Services;

use App\Models\Pairing;
use App\Models\Round;
use Illuminate\Support\Collection;

class KeizerPairingEngine extends AbstractPairingEngine
{
    /**
     * Generate Keizer pairings for the given round.
     * Pairs #1 vs #2, #3 vs #4 etc. by Keizer points,
     * skipping opponents who violate the re-pairing rule.
     *
     * @return Collection<Pairing>
     */
    public function generatePairings(Round $round): Collection
    {
        $round->load('period.season', 'period.rounds');

        $players = $this->getAvailablePlayers($round);

        if ($players->isEmpty()) {
            return collect();
        }

        // Get Keizer points for each player from the previous round's standings
        $playerPoints = $this->getPlayerPoints($round);

        // Sort players by points descending, with ELO as tiebreaker
        $players = $players->sortByDesc(function ($player) use ($playerPoints) {
            $points = $playerPoints[$player->id] ?? 0;
            $eloTiebreaker = ($player->elo_rating ?? 1200) / 100000;

            return $points + $eloTiebreaker;
        })->values();

        // Get previous pairings in this period for re-pairing checks
        $periodPairings = $this->getPeriodPairings($round);

        // Get color history for all players in this season
        $colorHistory = $this->getColorHistory($round);

        // Get bye history for all players in this season
        $byeHistory = $this->getByeHistory($round);

        $pairings = collect();
        $boardNumber = 1;

        // Handle bye if odd number of players
        $byePlayer = null;
        if ($players->count() % 2 === 1) {
            $byePlayer = $this->selectByePlayer($players, $byeHistory);
            $players = $players->reject(fn ($p) => $p->id === $byePlayer->id)->values();
        }

        // Keizer pairing: highest unpaired vs next highest unpaired
        $paired = [];
        $playerList = $players->all();

        for ($i = 0; $i < count($playerList); $i++) {
            if (in_array($playerList[$i]->id, $paired)) {
                continue;
            }

            $player1 = $playerList[$i];

            // Find the next available opponent
            for ($j = $i + 1; $j < count($playerList); $j++) {
                if (in_array($playerList[$j]->id, $paired)) {
                    continue;
                }

                $player2 = $playerList[$j];

                // Check re-pairing rule
                if ($this->cannotPair($player1->id, $player2->id, $periodPairings)) {
                    continue;
                }

                // Found a valid opponent
                [$whiteId, $blackId] = $this->assignColors($player1->id, $player2->id, $colorHistory);

                $pairing = Pairing::create([
                    'round_id' => $round->id,
                    'board_number' => $boardNumber++,
                    'white_user_id' => $whiteId,
                    'black_user_id' => $blackId,
                    'is_bye' => false,
                    'bye_user_id' => null,
                ]);

                $pairings->push($pairing);
                $paired[] = $player1->id;
                $paired[] = $player2->id;
                break;
            }

            // If no valid opponent found, the player remains unpaired
            // They will get a bye if no bye player was already assigned
        }

        // Handle any remaining unpaired players
        $unpairedPlayers = collect($playerList)->filter(fn ($p) => ! in_array($p->id, $paired));

        if ($unpairedPlayers->count() >= 2) {
            $remaining = $unpairedPlayers->values();

            // If odd remaining and no bye assigned yet, select bye player
            // using bye history before attempting backtracking
            if ($remaining->count() % 2 === 1 && $byePlayer === null) {
                $byePlayer = $this->selectByePlayer($remaining, $byeHistory);
                $remaining = $remaining->reject(fn ($p) => $p->id === $byePlayer->id)->values();
            }

            // One-level backtracking: try undoing a previous pairing to free up a
            // valid opponent for the stranded player(s), avoiding a repeat pairing.
            $backtrackResolved = false;

            if ($remaining->count() >= 2) {
                // Walk previously made pairings in reverse and try to undo each one
                $gamePairings = $pairings->where('is_bye', false)->values();

                for ($undoIdx = $gamePairings->count() - 1; $undoIdx >= 0; $undoIdx--) {
                    $undoPairing = $gamePairings[$undoIdx];

                    // Tentatively free both players of this pairing
                    $freedA = collect($playerList)->first(fn ($p) => $p->id === $undoPairing->white_user_id)
                        ?? collect($playerList)->first(fn ($p) => $p->id === $undoPairing->black_user_id);
                    $freedB = collect($playerList)->first(fn ($p) => $p->id === $undoPairing->black_user_id)
                        ?? collect($playerList)->first(fn ($p) => $p->id === $undoPairing->white_user_id);

                    if (! $freedA || ! $freedB || $freedA->id === $freedB->id) {
                        continue;
                    }

                    // Build the pool: stranded players + the two freed players
                    $pool = $remaining->merge([$freedA, $freedB])->unique('id')->values();

                    // If odd pool and no bye yet, handle bye selection before trying to pair
                    $poolByePlayer = null;
                    $pairingPool = $pool;
                    if ($pool->count() % 2 === 1) {
                        // Only apply bye logic here if we can — skip this undo candidate if
                        // a bye is already assigned and pool is still odd
                        if ($byePlayer !== null) {
                            continue;
                        }
                        $poolByePlayer = $this->selectByePlayer($pool, $byeHistory);
                        $pairingPool = $pool->reject(fn ($p) => $p->id === $poolByePlayer->id)->values();
                    }

                    // Try to find a complete valid pairing of the pool without any repeats.
                    // We attempt all permutations via a simple recursive backtracking helper.
                    $newPairs = $this->findValidPairing($pairingPool->all(), [], $periodPairings);

                    if ($newPairs !== null) {
                        // Success — delete the undone DB pairing and rebuild
                        $undoPairing->delete();
                        $pairings = $pairings->reject(fn ($p) => $p->id === $undoPairing->id)->values();

                        foreach ($newPairs as [$p1id, $p2id]) {
                            [$whiteId, $blackId] = $this->assignColors($p1id, $p2id, $colorHistory);
                            $newPairing = Pairing::create([
                                'round_id' => $round->id,
                                'board_number' => $boardNumber++,
                                'white_user_id' => $whiteId,
                                'black_user_id' => $blackId,
                                'is_bye' => false,
                                'bye_user_id' => null,
                            ]);
                            $pairings->push($newPairing);
                        }

                        if ($poolByePlayer !== null) {
                            $byePlayer = $poolByePlayer;
                        }

                        $backtrackResolved = true;
                        break;
                    }
                }
            }

            // Last resort: backtracking failed — force-pair stranded players ignoring re-pairing rule
            if (! $backtrackResolved) {
                for ($i = 0; $i + 1 < $remaining->count(); $i += 2) {
                    [$whiteId, $blackId] = $this->assignColors(
                        $remaining[$i]->id,
                        $remaining[$i + 1]->id,
                        $colorHistory
                    );

                    $pairing = Pairing::create([
                        'round_id' => $round->id,
                        'board_number' => $boardNumber++,
                        'white_user_id' => $whiteId,
                        'black_user_id' => $blackId,
                        'is_bye' => false,
                        'bye_user_id' => null,
                    ]);

                    $pairings->push($pairing);
                }
            }
        } elseif ($unpairedPlayers->count() === 1 && $byePlayer === null) {
            $byePlayer = $unpairedPlayers->first();
        }

        // Create bye pairing
        if ($byePlayer) {
            $pairing = Pairing::create([
                'round_id' => $round->id,
                'board_number' => $boardNumber,
                'white_user_id' => null,
                'black_user_id' => null,
                'is_bye' => true,
                'bye_user_id' => $byePlayer->id,
            ]);

            $pairings->push($pairing);
        }

        return $pairings;
    }

    /**
     * Recursively find a complete valid pairing of $players (already even count)
     * such that no pair violates the re-pairing rule.
     * Returns an array of [p1id, p2id] tuples, or null if none exists.
     *
     * @param  array  $players  Ordered player objects still to be paired
     * @param  array  $built  Pairs found so far (array of [id, id])
     */
    private function findValidPairing(array $players, array $built, Collection $periodPairings): ?array
    {
        if (count($players) === 0) {
            return $built;
        }

        $first = $players[0];
        $rest = array_slice($players, 1);

        foreach ($rest as $idx => $candidate) {
            if ($this->cannotPair($first->id, $candidate->id, $periodPairings)) {
                continue;
            }

            // Remove candidate from remaining pool
            $remaining = array_values(array_filter($rest, fn ($p, $i) => $i !== $idx, ARRAY_FILTER_USE_BOTH));

            $result = $this->findValidPairing($remaining, array_merge($built, [[$first->id, $candidate->id]]), $periodPairings);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}

<?php

namespace App\Services;

use App\Models\Pairing;
use App\Models\Round;
use Illuminate\Support\Collection;

class SwissPairingEngine extends AbstractPairingEngine
{
    /**
     * Generate Swiss pairings for the given round.
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

        // Get game scores (W=1, D=0.5, L=0, Bye=1) for bracket grouping
        $gameScores = $this->getGameScores($round);

        // Sort players by ELO rating descending (fixed initial seeding per FIDE C.04.3).
        // This determines the pairing number: highest ELO = PNr 1.
        // The pairing number is fixed and does not change during the period.
        // Keizer points are NOT used for Swiss pairing order.
        $players = $players->sortByDesc(function ($player) {
            return $player->elo_rating ?? 1200;
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

        // Group players into score brackets based on game scores
        $brackets = $this->groupIntoBrackets($players, $gameScores);

        // Pair within brackets with floating
        $paired = [];
        $unpaired = collect();

        foreach ($brackets as $bracketPoints => $bracketPlayers) {
            // Add any floaters from the previous bracket
            $bracketPlayers = $unpaired->merge($bracketPlayers)->values();
            $unpaired = collect();

            // If odd number in bracket, float the lowest player down
            if ($bracketPlayers->count() % 2 === 1) {
                $floater = $bracketPlayers->pop();
                $unpaired->push($floater);
            }

            if ($bracketPlayers->count() < 2) {
                if ($bracketPlayers->count() === 1) {
                    $unpaired->push($bracketPlayers->first());
                }

                continue;
            }

            // Pair top half vs bottom half: 1 vs N/2+1, 2 vs N/2+2, etc.
            $half = (int) ceil($bracketPlayers->count() / 2);
            $topHalf = $bracketPlayers->slice(0, $half)->values();
            $bottomHalf = $bracketPlayers->slice($half)->values();

            for ($i = 0; $i < $bottomHalf->count(); $i++) {
                $player1 = $topHalf[$i];
                $player2 = $bottomHalf[$i];

                // Check re-pairing rule and color conflicts
                if ($this->cannotPair($player1->id, $player2->id, $periodPairings)
                    || $this->hasColorConflict($player1->id, $player2->id, $colorHistory)) {
                    // Try to find an alternative opponent from the bottom half
                    $alternativeFound = false;
                    for ($j = $i + 1; $j < $bottomHalf->count(); $j++) {
                        if (! $this->cannotPair($player1->id, $bottomHalf[$j]->id, $periodPairings)
                            && ! $this->hasColorConflict($player1->id, $bottomHalf[$j]->id, $colorHistory)) {
                            // Swap bottom half players
                            $temp = $bottomHalf[$j];
                            $bottomHalf[$j] = $player2;
                            $player2 = $temp;
                            $alternativeFound = true;
                            break;
                        }
                    }

                    if (! $alternativeFound) {
                        // Float player1 down to the next bracket instead of pairing anyway
                        $unpaired->push($player1);
                        // player2 is now unmatched in this bracket — float them down too
                        $unpaired->push($player2);

                        continue;
                    }
                }

                // Determine colors
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
            }
        }

        // Handle any remaining unpaired players (floated down from brackets above)
        if ($unpaired->count() >= 2) {
            $remaining = $unpaired->values();

            // Try to find a complete valid pairing respecting the re-pairing rule.
            $validPairs = $this->findValidPairingSwiss($remaining->all(), [], $periodPairings, $colorHistory);

            if ($validPairs !== null) {
                foreach ($validPairs as [$p1id, $p2id]) {
                    [$whiteId, $blackId] = $this->assignColors($p1id, $p2id, $colorHistory);

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

                if ($remaining->count() % 2 === 1 && $byePlayer === null) {
                    $byePlayer = $remaining->last();
                }
            } else {
                // Last resort: no valid combination exists — force-pair ignoring re-pairing rule
                for ($i = 0; $i + 1 < $remaining->count(); $i += 2) {
                    $player1 = $remaining[$i];
                    $player2 = $remaining[$i + 1];

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
                }

                if ($remaining->count() % 2 === 1 && $byePlayer === null) {
                    $byePlayer = $remaining->last();
                }
            }
        } elseif ($unpaired->count() === 1 && $byePlayer === null) {
            $byePlayer = $unpaired->first();
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
     * Group players into score brackets based on their game scores (W=1, D=0.5, L=0).
     * Players within each bracket maintain their Keizer/ELO ordering.
     * Returns an ordered collection of brackets (highest game score first).
     */
    protected function groupIntoBrackets(Collection $players, array $gameScores): Collection
    {
        $brackets = collect();

        foreach ($players as $player) {
            $score = $gameScores[$player->id] ?? 0;
            // Use string key with 1 decimal to group (0, 0.5, 1, 1.5, etc.)
            $key = number_format($score, 1);

            if (! $brackets->has($key)) {
                $brackets[$key] = collect();
            }

            $brackets[$key]->push($player);
        }

        // Sort brackets by game score descending
        return $brackets->sortByDesc(fn ($v, $key) => (float) $key);
    }

    /**
     * Recursively find a complete valid pairing of $players (even count)
     * such that no pair violates the re-pairing rule or has a hard color conflict.
     * Returns an array of [p1id, p2id] tuples, or null if none exists.
     *
     * @param  array  $players  Player objects still to be paired
     * @param  array  $built  Pairs found so far (array of [id, id])
     */
    private function findValidPairingSwiss(array $players, array $built, Collection $periodPairings, array $colorHistory): ?array
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
            // Don't skip on color conflict alone — assignColors() resolves soft conflicts.
            // Only skip if it's a hard color conflict (both must play the same color).
            if ($this->hasColorConflict($first->id, $candidate->id, $colorHistory)) {
                continue;
            }

            $remaining = array_values(array_filter($rest, fn ($p, $i) => $i !== $idx, ARRAY_FILTER_USE_BOTH));

            $result = $this->findValidPairingSwiss($remaining, array_merge($built, [[$first->id, $candidate->id]]), $periodPairings, $colorHistory);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}

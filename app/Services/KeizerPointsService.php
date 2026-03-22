<?php

namespace App\Services;

use App\Models\Pairing;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Standing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KeizerPointsService
{
    private int $maxWaardering;

    private float $puntenExtern;

    private float $puntenAfwezig;

    private float $puntenOneven;

    private int $maxAfwezig;

    private float $puntenNieuweSpeler;

    private float $factorWinst;

    private float $factorRemise;

    private float $factorVerlies;

    public function __construct()
    {
        $settings = Setting::pluck('value', 'key');

        $this->maxWaardering = (int) ($settings['max_waardering'] ?? 60);
        $this->puntenExtern = (float) ($settings['punten_extern'] ?? 40);
        $this->puntenAfwezig = (float) ($settings['punten_afwezig'] ?? 20);
        $this->puntenOneven = (float) ($settings['punten_oneven'] ?? 40);
        $this->maxAfwezig = (int) ($settings['max_afwezig'] ?? 5);
        $this->puntenNieuweSpeler = (float) ($settings['punten_nieuwe_speler'] ?? 15);
        $this->factorWinst = (float) ($settings['factor_winst'] ?? 1);
        $this->factorRemise = (float) ($settings['factor_remise'] ?? 0.5);
        $this->factorVerlies = (float) ($settings['factor_verlies'] ?? 0);
    }

    /**
     * Full recalculation of all standings for the season.
     * Iterates through all completed rounds in order, recalculating rank values
     * and points from scratch.
     */
    public function recalculateStandings(Season $season): void
    {
        DB::transaction(function () use ($season) {
            $this->performRecalculation($season);
        });
    }

    private function performRecalculation(Season $season): void
    {
        // Get all completed rounds for this season, ordered by season_round_number
        $rounds = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })
            ->where('status', 'completed')
            ->orderBy('season_round_number')
            ->get();

        if ($rounds->isEmpty()) {
            return;
        }

        // Get all active players who have participated in this season
        $playerIds = $this->getSeasonPlayerIds($rounds);

        // Identify players who joined mid-season
        $midSeasonJoiners = User::whereIn('id', $playerIds)
            ->whereNotNull('joined_at_round_id')
            ->pluck('joined_at_round_id', 'id'); // user_id => round_id

        // Map joined_at_round_id to season_round_number for quick lookup
        $joinedAtRoundNumbers = [];
        if ($midSeasonJoiners->isNotEmpty()) {
            $roundNumberMap = Round::whereIn('id', $midSeasonJoiners->values())
                ->pluck('season_round_number', 'id');
            foreach ($midSeasonJoiners as $userId => $roundId) {
                $joinedAtRoundNumbers[$userId] = $roundNumberMap[$roundId] ?? 1;
            }
        }

        // Track cumulative points and stats across all rounds
        $cumulativePoints = []; // user_id => total points
        $playerStats = []; // user_id => [wins, draws, losses, external, bye, absence, color_balance, games_played]
        $previousStandings = null; // standings from previous round for position_change

        foreach ($rounds as $index => $round) {
            // Determine rank values for this round
            if ($index === 0) {
                $rankValues = $this->getInitialRankValues($season);
            } else {
                $rankValues = $this->getRankValues($cumulativePoints);
            }

            // Calculate points for this round
            $roundPoints = $this->calculateRoundPoints($round, $rankValues);

            // Count absences up to and including this round for the threshold check
            $absenceCounts = $this->countAbsencesUpToRound($rounds, $round);

            // Apply absence threshold: if > max_afwezig, absence points become 0
            foreach ($roundPoints as $userId => &$pointData) {
                if ($pointData['type'] === 'absent') {
                    $totalAbsences = $absenceCounts[$userId] ?? 0;
                    if ($totalAbsences > $this->maxAfwezig) {
                        $pointData['points'] = 0;
                    }
                }
            }
            unset($pointData);

            // Handle mid-season joiners: give starting points for missed rounds
            foreach ($joinedAtRoundNumbers as $userId => $joinRoundNumber) {
                if ($round->season_round_number === $joinRoundNumber && $joinRoundNumber > 1) {
                    // This is the round they joined; give starting points for previous rounds
                    $missedRounds = $joinRoundNumber - 1;
                    $startingPoints = $missedRounds * $this->puntenNieuweSpeler;
                    $cumulativePoints[$userId] = ($cumulativePoints[$userId] ?? 0) + $startingPoints;
                }
            }

            // Accumulate points and stats
            foreach ($roundPoints as $userId => $pointData) {
                $cumulativePoints[$userId] = ($cumulativePoints[$userId] ?? 0) + $pointData['points'];

                if (! isset($playerStats[$userId])) {
                    $playerStats[$userId] = [
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'external_count' => 0,
                        'bye_count' => 0,
                        'absence_count' => 0,
                        'color_balance' => 0,
                        'games_played' => 0,
                    ];
                }

                switch ($pointData['type']) {
                    case 'win':
                        $playerStats[$userId]['wins']++;
                        $playerStats[$userId]['games_played']++;
                        $playerStats[$userId]['color_balance'] += $pointData['color'] ?? 0;
                        break;
                    case 'draw':
                        $playerStats[$userId]['draws']++;
                        $playerStats[$userId]['games_played']++;
                        $playerStats[$userId]['color_balance'] += $pointData['color'] ?? 0;
                        break;
                    case 'loss':
                        $playerStats[$userId]['losses']++;
                        $playerStats[$userId]['games_played']++;
                        $playerStats[$userId]['color_balance'] += $pointData['color'] ?? 0;
                        break;
                    case 'external':
                        $playerStats[$userId]['external_count']++;
                        break;
                    case 'bye':
                        $playerStats[$userId]['bye_count']++;
                        break;
                    case 'absent':
                        $playerStats[$userId]['absence_count']++;
                        break;
                }
            }

            // Update standings for this round
            $previousStandings = $this->updateStandings($round, $cumulativePoints, $playerStats, $previousStandings);
        }
    }

    /**
     * Calculate points for a single round given rank values.
     * Returns an array of user_id => ['points' => float, 'type' => string, 'color' => int|null]
     */
    public function calculateRoundPoints(Round $round, array $rankValues): array
    {
        $roundPoints = [];

        // Load pairings for this round
        $pairings = Pairing::where('round_id', $round->id)->get();

        foreach ($pairings as $pairing) {
            if ($pairing->is_bye && $pairing->bye_user_id) {
                // Bye pairing
                $roundPoints[$pairing->bye_user_id] = [
                    'points' => $this->puntenOneven,
                    'type' => 'bye',
                    'color' => null,
                ];

                continue;
            }

            if (! $pairing->white_user_id || ! $pairing->black_user_id) {
                continue;
            }

            $whiteId = $pairing->white_user_id;
            $blackId = $pairing->black_user_id;
            $whiteRankValue = $rankValues[$whiteId] ?? 1;
            $blackRankValue = $rankValues[$blackId] ?? 1;

            switch ($pairing->result) {
                case '1-0':
                    // White wins
                    $roundPoints[$whiteId] = [
                        'points' => $this->factorWinst * $blackRankValue,
                        'type' => 'win',
                        'color' => 1, // white
                    ];
                    $roundPoints[$blackId] = [
                        'points' => $this->factorVerlies * $whiteRankValue,
                        'type' => 'loss',
                        'color' => -1, // black
                    ];
                    break;

                case '0-1':
                    // Black wins
                    $roundPoints[$whiteId] = [
                        'points' => $this->factorVerlies * $blackRankValue,
                        'type' => 'loss',
                        'color' => 1,
                    ];
                    $roundPoints[$blackId] = [
                        'points' => $this->factorWinst * $whiteRankValue,
                        'type' => 'win',
                        'color' => -1,
                    ];
                    break;

                case 'remise':
                    // Draw
                    $roundPoints[$whiteId] = [
                        'points' => $this->factorRemise * $blackRankValue,
                        'type' => 'draw',
                        'color' => 1,
                    ];
                    $roundPoints[$blackId] = [
                        'points' => $this->factorRemise * $whiteRankValue,
                        'type' => 'draw',
                        'color' => -1,
                    ];
                    break;

                default:
                    // No result yet or unknown — skip
                    break;
            }
        }

        // Load player statuses for external and absent players
        $statuses = RoundPlayerStatus::where('round_id', $round->id)->get();

        foreach ($statuses as $status) {
            // Skip players who already have points from a pairing
            if (isset($roundPoints[$status->user_id])) {
                continue;
            }

            switch ($status->status) {
                case 'external':
                    $roundPoints[$status->user_id] = [
                        'points' => $this->puntenExtern,
                        'type' => 'external',
                        'color' => null,
                    ];
                    break;

                case 'absent':
                    $roundPoints[$status->user_id] = [
                        'points' => $this->puntenAfwezig,
                        'type' => 'absent',
                        'color' => null,
                    ];
                    break;

                case 'bye':
                    // Bye recorded via status rather than pairing
                    if (! isset($roundPoints[$status->user_id])) {
                        $roundPoints[$status->user_id] = [
                            'points' => $this->puntenOneven,
                            'type' => 'bye',
                            'color' => null,
                        ];
                    }
                    break;
            }
        }

        return $roundPoints;
    }

    /**
     * Convert standings positions to rank values.
     * Position 1 = max_waardering, 2 = max_waardering - 1, etc. Minimum 1.
     */
    public function getRankValues(array $cumulativePoints): array
    {
        // Sort by points descending
        arsort($cumulativePoints);

        $rankValues = [];
        $position = 1;

        foreach ($cumulativePoints as $userId => $points) {
            $rankValue = max(1, $this->maxWaardering - ($position - 1));
            $rankValues[$userId] = $rankValue;
            $position++;
        }

        return $rankValues;
    }

    /**
     * Get rank values based on elo_rating for round 1.
     * Players with higher ELO get higher rank values.
     * Players without elo_rating default to 1200.
     */
    public function getInitialRankValues(Season $season): array
    {
        // Get all players who participate in this season
        $rounds = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })->pluck('id');

        $playerIds = $this->getPlayerIdsFromRoundIds($rounds->toArray());

        // Load users with their elo_rating, sorted by ELO descending
        $players = User::whereIn('id', $playerIds)
            ->get()
            ->sortByDesc(fn ($user) => $user->elo_rating ?? 1200)
            ->values();

        $rankValues = [];
        $position = 1;

        foreach ($players as $player) {
            $rankValue = max(1, $this->maxWaardering - ($position - 1));
            $rankValues[$player->id] = $rankValue;
            $position++;
        }

        return $rankValues;
    }

    /**
     * Create/update Standing records with positions, points, and stats.
     * Returns an array of user_id => position for tracking position changes.
     */
    public function updateStandings(
        Round $round,
        array $cumulativePoints,
        array $playerStats,
        ?array $previousPositions = null
    ): array {
        // Sort players by total points descending to determine positions
        arsort($cumulativePoints);

        $position = 1;
        $currentPositions = [];

        foreach ($cumulativePoints as $userId => $totalPoints) {
            $stats = $playerStats[$userId] ?? [
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'external_count' => 0,
                'bye_count' => 0,
                'absence_count' => 0,
                'color_balance' => 0,
                'games_played' => 0,
            ];

            // Calculate position change
            $positionChange = 0;
            if ($previousPositions !== null && isset($previousPositions[$userId])) {
                // Positive = moved up (lower position number = higher rank)
                $positionChange = $previousPositions[$userId] - $position;
            }

            Standing::updateOrCreate(
                [
                    'round_id' => $round->id,
                    'user_id' => $userId,
                ],
                [
                    'position' => $position,
                    'position_change' => $positionChange,
                    'points' => round($totalPoints, 1),
                    'games_played' => $stats['games_played'],
                    'color_balance' => $stats['color_balance'],
                    'wins' => $stats['wins'],
                    'draws' => $stats['draws'],
                    'losses' => $stats['losses'],
                    'external_count' => $stats['external_count'],
                    'bye_count' => $stats['bye_count'],
                    'absence_count' => $stats['absence_count'],
                ]
            );

            $currentPositions[$userId] = $position;
            $position++;
        }

        // Remove standings for this round that no longer apply
        Standing::where('round_id', $round->id)
            ->whereNotIn('user_id', array_keys($cumulativePoints))
            ->delete();

        return $currentPositions;
    }

    /**
     * Count absences for each player up to and including the given round.
     */
    private function countAbsencesUpToRound($rounds, Round $currentRound): array
    {
        $absenceCounts = [];
        foreach ($rounds as $round) {
            $statuses = RoundPlayerStatus::where('round_id', $round->id)
                ->where('status', 'absent')
                ->pluck('user_id');

            foreach ($statuses as $userId) {
                $absenceCounts[$userId] = ($absenceCounts[$userId] ?? 0) + 1;
            }

            if ($round->id === $currentRound->id) {
                break;
            }
        }

        return $absenceCounts;
    }

    /**
     * Get all player IDs who have participated in any of the given rounds.
     */
    private function getSeasonPlayerIds($rounds): array
    {
        $roundIds = $rounds->pluck('id')->toArray();

        return $this->getPlayerIdsFromRoundIds($roundIds);
    }

    /**
     * Get player IDs from pairings and round player statuses for given round IDs.
     */
    private function getPlayerIdsFromRoundIds(array $roundIds): array
    {
        $fromPairingsWhite = Pairing::whereIn('round_id', $roundIds)
            ->whereNotNull('white_user_id')
            ->pluck('white_user_id');

        $fromPairingsBlack = Pairing::whereIn('round_id', $roundIds)
            ->whereNotNull('black_user_id')
            ->pluck('black_user_id');

        $fromPairingsBye = Pairing::whereIn('round_id', $roundIds)
            ->whereNotNull('bye_user_id')
            ->pluck('bye_user_id');

        $fromStatuses = RoundPlayerStatus::whereIn('round_id', $roundIds)
            ->pluck('user_id');

        return $fromPairingsWhite
            ->merge($fromPairingsBlack)
            ->merge($fromPairingsBye)
            ->merge($fromStatuses)
            ->unique()
            ->values()
            ->toArray();
    }
}

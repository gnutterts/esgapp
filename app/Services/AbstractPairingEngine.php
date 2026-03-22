<?php

namespace App\Services;

use App\Models\Pairing;
use App\Models\Registration;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Standing;
use App\Models\User;
use Illuminate\Support\Collection;

abstract class AbstractPairingEngine
{
    abstract public function generatePairings(Round $round): Collection;

    protected function getAvailablePlayers(Round $round): Collection
    {
        $registrations = Registration::where('round_id', $round->id)
            ->where('status', 'available')
            ->with('user')
            ->get();

        $players = $registrations
            ->filter(fn ($reg) => $reg->user && $reg->user->is_active)
            ->map(fn ($reg) => $reg->user);

        // Safety net: include auto-participate users who have no registration at all
        // for this round (they should have been materialized at closeRegistration,
        // but this ensures they are never missed).
        $registeredUserIds = Registration::where('round_id', $round->id)
            ->pluck('user_id');

        $autoParticipateUsers = User::where('auto_participate', true)
            ->where('is_active', true)
            ->whereNotIn('id', $registeredUserIds)
            ->get();

        return $players->merge($autoParticipateUsers)->unique('id')->values();
    }

    protected function getPlayerPoints(Round $round): array
    {
        $season = $round->period->season;

        $previousRound = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })
            ->where('season_round_number', '<', $round->season_round_number)
            ->where('status', 'completed')
            ->orderByDesc('season_round_number')
            ->first();

        if (! $previousRound) {
            return [];
        }

        return Standing::where('round_id', $previousRound->id)
            ->pluck('points', 'user_id')
            ->toArray();
    }

    /**
     * Get Swiss-style game scores (W=1, D=0.5, L=0, Bye=1) for each player
     * based on all completed rounds in the season before the given round.
     */
    protected function getGameScores(Round $round): array
    {
        $season = $round->period->season;

        $completedRoundIds = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })
            ->where('season_round_number', '<', $round->season_round_number)
            ->where('status', 'completed')
            ->pluck('id');

        if ($completedRoundIds->isEmpty()) {
            return [];
        }

        $pairings = Pairing::whereIn('round_id', $completedRoundIds)->get();

        $scores = [];

        foreach ($pairings as $pairing) {
            if ($pairing->is_bye && $pairing->bye_user_id) {
                // Bye = 1 point in Swiss
                $scores[$pairing->bye_user_id] = ($scores[$pairing->bye_user_id] ?? 0) + 1;

                continue;
            }

            if (! $pairing->result || ! $pairing->white_user_id || ! $pairing->black_user_id) {
                continue;
            }

            $whiteId = $pairing->white_user_id;
            $blackId = $pairing->black_user_id;

            switch ($pairing->result) {
                case '1-0':
                    $scores[$whiteId] = ($scores[$whiteId] ?? 0) + 1;
                    $scores[$blackId] = ($scores[$blackId] ?? 0);
                    break;
                case '0-1':
                    $scores[$whiteId] = ($scores[$whiteId] ?? 0);
                    $scores[$blackId] = ($scores[$blackId] ?? 0) + 1;
                    break;
                case 'remise':
                    $scores[$whiteId] = ($scores[$whiteId] ?? 0) + 0.5;
                    $scores[$blackId] = ($scores[$blackId] ?? 0) + 0.5;
                    break;
                    // '*' or other: no points awarded
            }
        }

        return $scores;
    }

    protected function getPeriodPairings(Round $round): Collection
    {
        $periodRoundIds = $round->period->rounds
            ->where('id', '!=', $round->id)
            ->pluck('id');

        if ($periodRoundIds->isEmpty()) {
            return collect();
        }

        return Pairing::whereIn('round_id', $periodRoundIds)
            ->where('is_bye', false)
            ->get();
    }

    protected function cannotPair(int $player1Id, int $player2Id, Collection $periodPairings): bool
    {
        $previousGames = $periodPairings->filter(function ($pairing) use ($player1Id, $player2Id) {
            return ($pairing->white_user_id === $player1Id && $pairing->black_user_id === $player2Id)
                || ($pairing->white_user_id === $player2Id && $pairing->black_user_id === $player1Id);
        });

        foreach ($previousGames as $game) {
            $whiteStatus = RoundPlayerStatus::where('round_id', $game->round_id)
                ->where('user_id', $game->white_user_id)
                ->first();
            $blackStatus = RoundPlayerStatus::where('round_id', $game->round_id)
                ->where('user_id', $game->black_user_id)
                ->first();

            $whiteAbsent = $whiteStatus && $whiteStatus->status === 'absent';
            $blackAbsent = $blackStatus && $blackStatus->status === 'absent';

            if ($whiteAbsent || $blackAbsent) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function getColorHistory(Round $round): array
    {
        $season = $round->period->season;

        $seasonRoundIds = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })
            ->where('id', '!=', $round->id)
            ->pluck('id');

        if ($seasonRoundIds->isEmpty()) {
            return [];
        }

        $pairings = Pairing::whereIn('round_id', $seasonRoundIds)
            ->where('is_bye', false)
            ->join('rounds', 'pairings.round_id', '=', 'rounds.id')
            ->orderBy('rounds.season_round_number')
            ->orderBy('pairings.board_number')
            ->select('pairings.*')
            ->get();

        $whiteCount = [];
        $totalGames = [];
        $lastColor = [];
        $consecutiveSame = [];

        foreach ($pairings as $pairing) {
            if ($pairing->white_user_id) {
                $uid = $pairing->white_user_id;
                $whiteCount[$uid] = ($whiteCount[$uid] ?? 0) + 1;
                $totalGames[$uid] = ($totalGames[$uid] ?? 0) + 1;

                // Track consecutive same color
                if (($lastColor[$uid] ?? null) === 'white') {
                    $consecutiveSame[$uid] = ($consecutiveSame[$uid] ?? 1) + 1;
                } else {
                    $consecutiveSame[$uid] = 1;
                }
                $lastColor[$uid] = 'white';
            }
            if ($pairing->black_user_id) {
                $uid = $pairing->black_user_id;
                $totalGames[$uid] = ($totalGames[$uid] ?? 0) + 1;

                // Track consecutive same color
                if (($lastColor[$uid] ?? null) === 'black') {
                    $consecutiveSame[$uid] = ($consecutiveSame[$uid] ?? 1) + 1;
                } else {
                    $consecutiveSame[$uid] = 1;
                }
                $lastColor[$uid] = 'black';
            }
        }

        return [
            'whiteCount' => $whiteCount,
            'totalGames' => $totalGames,
            'lastColor' => $lastColor,
            'consecutiveSame' => $consecutiveSame,
        ];
    }

    /**
     * Assign colors based on Keizer specification priority:
     * 1. Player with lowest white-percentage gets white
     * 2. If equal percentage, alternate from last color played
     * 3. If still equal, first player (higher-ranked) gets white
     *
     * Additionally enforces:
     * - No 3 consecutive games with the same color
     * - Color difference max ±2
     */
    protected function assignColors(int $player1Id, int $player2Id, array $colorHistory): array
    {
        $whiteCount = $colorHistory['whiteCount'] ?? [];
        $totalGames = $colorHistory['totalGames'] ?? [];
        $lastColor = $colorHistory['lastColor'] ?? [];
        $consecutiveSame = $colorHistory['consecutiveSame'] ?? [];

        $p1White = $whiteCount[$player1Id] ?? 0;
        $p1Total = $totalGames[$player1Id] ?? 0;
        $p2White = $whiteCount[$player2Id] ?? 0;
        $p2Total = $totalGames[$player2Id] ?? 0;
        $p1Black = $p1Total - $p1White;
        $p2Black = $p2Total - $p2White;

        $p1Last = $lastColor[$player1Id] ?? null;
        $p2Last = $lastColor[$player2Id] ?? null;
        $p1Consecutive = $consecutiveSame[$player1Id] ?? 0;
        $p2Consecutive = $consecutiveSame[$player2Id] ?? 0;

        // Hard constraint: no 3 consecutive games with the same color
        $p1MustPlayBlack = ($p1Consecutive >= 2 && $p1Last === 'white');
        $p1MustPlayWhite = ($p1Consecutive >= 2 && $p1Last === 'black');
        $p2MustPlayBlack = ($p2Consecutive >= 2 && $p2Last === 'white');
        $p2MustPlayWhite = ($p2Consecutive >= 2 && $p2Last === 'black');

        // Hard constraint: color difference max ±2
        // Balance = white - black. If balance is +2, must play black. If -2, must play white.
        $p1Balance = $p1White - $p1Black;
        $p2Balance = $p2White - $p2Black;

        if ($p1Balance >= 2) {
            $p1MustPlayBlack = true;
        }
        if ($p1Balance <= -2) {
            $p1MustPlayWhite = true;
        }
        if ($p2Balance >= 2) {
            $p2MustPlayBlack = true;
        }
        if ($p2Balance <= -2) {
            $p2MustPlayWhite = true;
        }

        // Resolve hard constraints first
        if ($p1MustPlayWhite && ! $p2MustPlayWhite) {
            return [$player1Id, $player2Id];
        }
        if ($p2MustPlayWhite && ! $p1MustPlayWhite) {
            return [$player2Id, $player1Id];
        }
        if ($p1MustPlayBlack && ! $p2MustPlayBlack) {
            return [$player2Id, $player1Id];
        }
        if ($p2MustPlayBlack && ! $p1MustPlayBlack) {
            return [$player1Id, $player2Id];
        }

        // Priority 1: Lowest white-percentage gets white
        $p1WhitePct = $p1Total > 0 ? $p1White / $p1Total : 0.5;
        $p2WhitePct = $p2Total > 0 ? $p2White / $p2Total : 0.5;

        if (abs($p1WhitePct - $p2WhitePct) > 0.001) {
            return $p1WhitePct < $p2WhitePct
                ? [$player1Id, $player2Id]
                : [$player2Id, $player1Id];
        }

        // Priority 2: Alternate from last color played
        $p1PreferWhite = ($p1Last === 'black' || $p1Last === null);
        $p2PreferWhite = ($p2Last === 'black' || $p2Last === null);

        if ($p1PreferWhite && ! $p2PreferWhite) {
            return [$player1Id, $player2Id];
        }
        if ($p2PreferWhite && ! $p1PreferWhite) {
            return [$player2Id, $player1Id];
        }

        // Priority 3: Higher-ranked player (player1 is always higher-ranked
        // since engines pass them in rank order) gets white
        return [$player1Id, $player2Id];
    }

    /**
     * Check if pairing two players would create an unresolvable color conflict.
     * Returns true if both players MUST play the same color (both must play white
     * or both must play black) due to hard constraints (3-in-a-row or ±2 limit).
     */
    protected function hasColorConflict(int $player1Id, int $player2Id, array $colorHistory): bool
    {
        $whiteCount = $colorHistory['whiteCount'] ?? [];
        $totalGames = $colorHistory['totalGames'] ?? [];
        $lastColor = $colorHistory['lastColor'] ?? [];
        $consecutiveSame = $colorHistory['consecutiveSame'] ?? [];

        $mustPlayWhite = [];
        $mustPlayBlack = [];

        foreach ([$player1Id, $player2Id] as $pid) {
            $white = $whiteCount[$pid] ?? 0;
            $total = $totalGames[$pid] ?? 0;
            $black = $total - $white;
            $last = $lastColor[$pid] ?? null;
            $consecutive = $consecutiveSame[$pid] ?? 0;

            $needsWhite = ($consecutive >= 2 && $last === 'black') || ($white - $black <= -2);
            $needsBlack = ($consecutive >= 2 && $last === 'white') || ($white - $black >= 2);

            if ($needsWhite) {
                $mustPlayWhite[] = $pid;
            }
            if ($needsBlack) {
                $mustPlayBlack[] = $pid;
            }
        }

        // Conflict: both must play white or both must play black
        return count($mustPlayWhite) >= 2 || count($mustPlayBlack) >= 2;
    }

    protected function getByeHistory(Round $round): Collection
    {
        $season = $round->period->season;

        $seasonRoundIds = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })
            ->where('id', '!=', $round->id)
            ->pluck('id');

        if ($seasonRoundIds->isEmpty()) {
            return collect();
        }

        // Players who received an actual bye
        $byePlayers = Pairing::whereIn('round_id', $seasonRoundIds)
            ->where('is_bye', true)
            ->whereNotNull('bye_user_id')
            ->pluck('bye_user_id');

        // Players who received a forfeit win (opponent was absent)
        // Per FIDE C.04.1.d, forfeit wins count similarly to byes
        $seasonPairings = Pairing::whereIn('round_id', $seasonRoundIds)
            ->where('is_bye', false)
            ->whereNotNull('white_user_id')
            ->whereNotNull('black_user_id')
            ->get();

        $forfeitWinners = collect();

        foreach ($seasonPairings as $pairing) {
            $whiteStatus = RoundPlayerStatus::where('round_id', $pairing->round_id)
                ->where('user_id', $pairing->white_user_id)
                ->first();
            $blackStatus = RoundPlayerStatus::where('round_id', $pairing->round_id)
                ->where('user_id', $pairing->black_user_id)
                ->first();

            // If opponent was absent, the other player got a forfeit win
            if ($blackStatus && $blackStatus->status === 'absent') {
                $forfeitWinners->push($pairing->white_user_id);
            }
            if ($whiteStatus && $whiteStatus->status === 'absent') {
                $forfeitWinners->push($pairing->black_user_id);
            }
        }

        return $byePlayers->merge($forfeitWinners)->unique();
    }

    protected function selectByePlayer(Collection $players, Collection $byeHistory): object
    {
        $reversed = $players->reverse();

        foreach ($reversed as $player) {
            if (! $byeHistory->contains($player->id)) {
                return $player;
            }
        }

        return $reversed->first();
    }
}

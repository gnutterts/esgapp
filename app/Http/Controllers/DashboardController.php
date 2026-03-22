<?php

namespace App\Http\Controllers;

use App\Models\Pairing;
use App\Models\Round;
use App\Models\Season;
use App\Models\Standing;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Load current season with periods and their rounds
        $season = Season::current()
            ->with(['periods' => function ($q) {
                $q->orderBy('number')->with(['rounds' => function ($q) {
                    $q->orderBy('season_round_number');
                }]);
            }])
            ->first();

        // Determine the current (active) period: last period that has a non-completed round, or simply the last period
        $currentPeriod = null;
        if ($season) {
            foreach ($season->periods as $period) {
                foreach ($period->rounds as $round) {
                    if (in_array($round->status, ['scheduled', 'registration_closed', 'paired'])) {
                        $currentPeriod = $period;
                        break 2;
                    }
                }
            }
            // Fallback: last period
            if (! $currentPeriod) {
                $currentPeriod = $season->periods->last();
            }
        }

        // Collect all rounds across the season
        $allRounds = collect();
        if ($season) {
            foreach ($season->periods as $period) {
                foreach ($period->rounds as $round) {
                    $round->setRelation('period', $period);
                    $allRounds->push($round);
                }
            }
        }

        // Upcoming/active rounds: scheduled, registration_closed, or paired — ordered by date ascending
        $upcomingRounds = $allRounds
            ->filter(fn ($r) => in_array($r->status, ['scheduled', 'registration_closed', 'paired']))
            ->sortBy('date')
            ->values();

        // Recent completed rounds: last 5, ordered by date descending
        $recentRounds = $allRounds
            ->filter(fn ($r) => $r->status === 'completed')
            ->sortByDesc('date')
            ->take(5)
            ->values();

        // Load user's registrations for all relevant round IDs
        $roundIds = $allRounds->pluck('id');
        $registrations = $user->registrations()
            ->whereIn('round_id', $roundIds)
            ->get()
            ->keyBy('round_id');

        // Load pairings for completed rounds with opponent names
        $completedRoundIds = $recentRounds->pluck('id');
        $pairings = Pairing::whereIn('round_id', $completedRoundIds)
            ->where(function ($q) use ($user) {
                $q->where('white_user_id', $user->id)
                    ->orWhere('black_user_id', $user->id)
                    ->orWhere('bye_user_id', $user->id);
            })
            ->with(['whitePlayer', 'blackPlayer', 'byePlayer'])
            ->get()
            ->keyBy('round_id');

        // Load standings for the recent rounds to show points earned per round
        $recentStandings = Standing::whereIn('round_id', $completedRoundIds)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('round_id');

        // For each recent round, we need the standing from the *previous* completed round
        // to compute points earned in that round (cumulative diff).
        $prevRoundIds = [];
        foreach ($recentRounds as $round) {
            $prevRound = $allRounds
                ->filter(fn ($r) => $r->status === 'completed' && $r->season_round_number < $round->season_round_number)
                ->sortByDesc('season_round_number')
                ->first();
            if ($prevRound) {
                $prevRoundIds[$round->id] = $prevRound->id;
            }
        }
        $prevStandings = Standing::whereIn('round_id', array_values($prevRoundIds))
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('round_id');

        // Load user's standing from the latest completed round
        $userStanding = null;
        $latestCompletedRound = $recentRounds->first();
        if ($latestCompletedRound) {
            $userStanding = Standing::where('round_id', $latestCompletedRound->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return view('dashboard', compact(
            'user',
            'season',
            'currentPeriod',
            'upcomingRounds',
            'recentRounds',
            'registrations',
            'pairings',
            'recentStandings',
            'prevRoundIds',
            'prevStandings',
            'userStanding',
            'latestCompletedRound'
        ));
    }
}

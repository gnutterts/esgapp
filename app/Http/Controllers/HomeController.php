<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\Season;
use App\Models\Standing;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $currentSeason = Season::current()
            ->with(['periods' => function ($q) {
                $q->orderBy('number')->with(['rounds' => function ($q) {
                    $q->orderBy('season_round_number');
                }]);
            }])
            ->first();

        $latestCompletedRound = null;
        $standings = collect();
        $nextRound = null;

        if ($currentSeason) {
            // Collect all rounds for this season
            $allRounds = collect();
            foreach ($currentSeason->periods as $period) {
                foreach ($period->rounds as $round) {
                    $round->setRelation('period', $period);
                    $allRounds->push($round);
                }
            }

            // Latest completed round
            $latestCompletedRound = $allRounds
                ->filter(fn ($r) => $r->status === 'completed')
                ->sortByDesc('season_round_number')
                ->first();

            if ($latestCompletedRound) {
                $standings = Standing::where('round_id', $latestCompletedRound->id)
                    ->with('user')
                    ->orderBy('position')
                    ->take(10)
                    ->get();
            }

            // Next upcoming rounds: first two rounds that are not completed
            $nextRounds = $allRounds
                ->filter(fn ($r) => $r->status !== 'completed')
                ->sortBy('season_round_number')
                ->take(3)
                ->values();
        }

        return view('home', [
            'currentSeason' => $currentSeason,
            'latestCompletedRound' => $latestCompletedRound,
            'standings' => $standings,
            'nextRounds' => $nextRounds ?? collect(),
        ]);
    }
}

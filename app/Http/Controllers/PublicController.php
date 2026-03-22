<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Standing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Full standings page — shows the standings of the latest completed round.
     */
    public function stand(): View
    {
        $currentSeason = Season::current()->first();

        $latestCompletedRound = null;
        $standings = collect();

        if ($currentSeason) {
            $latestCompletedRound = Round::whereHas('period', fn ($q) => $q->where('season_id', $currentSeason->id))
                ->where('status', 'completed')
                ->orderByDesc('season_round_number')
                ->first();

            if ($latestCompletedRound) {
                $standings = Standing::where('round_id', $latestCompletedRound->id)
                    ->with('user')
                    ->orderBy('position')
                    ->get();
            }
        }

        $eindstand = $this->filterMinDeelname($standings);

        $isLastRound = $latestCompletedRound?->season_round_number === 24;

        return view('stand', [
            'currentSeason' => $currentSeason,
            'latestCompletedRound' => $latestCompletedRound,
            'standings' => $standings,
            'eindstand' => $eindstand,
            'isLastRound' => $isLastRound,
        ]);
    }

    /**
     * Pairings page for a specific round.
     * Only visible when status is 'paired' or 'completed'.
     */
    public function indeling(Round $round): View|RedirectResponse
    {
        if (! in_array($round->status, ['paired', 'completed'])) {
            return redirect()->route('home');
        }

        $round->load(['period.season']);

        $pairings = $round->pairings()
            ->with(['whitePlayer', 'blackPlayer', 'byePlayer'])
            ->where('is_bye', false)
            ->orderBy('board_number')
            ->get();

        $byePairing = $round->pairings()
            ->with('byePlayer')
            ->where('is_bye', true)
            ->first();

        // Previous/next rounds with pairings visible (paired or completed) in the same season
        [$previousRound, $nextRound] = $this->adjacentRounds(
            $round,
            ['paired', 'completed']
        );

        $allRounds = $this->seasonRounds($round->period->season_id);

        return view('indeling', [
            'round' => $round,
            'pairings' => $pairings,
            'byePairing' => $byePairing,
            'previousRound' => $previousRound,
            'nextRound' => $nextRound,
            'allRounds' => $allRounds,
        ]);
    }

    /**
     * Results page for a completed round.
     */
    public function uitslag(Round $round): View|RedirectResponse
    {
        if ($round->status !== 'completed') {
            return redirect()->route('home');
        }

        $round->load(['period.season']);

        $pairings = $round->pairings()
            ->with(['whitePlayer', 'blackPlayer', 'byePlayer'])
            ->where('is_bye', false)
            ->orderBy('board_number')
            ->get();

        $byePairing = $round->pairings()
            ->with('byePlayer')
            ->where('is_bye', true)
            ->first();

        $standings = Standing::where('round_id', $round->id)
            ->with('user')
            ->orderBy('position')
            ->get();

        $eindstand = $this->filterMinDeelname($standings);

        // Check for unconfirmed external games
        $voorlopig = RoundPlayerStatus::where('round_id', $round->id)
            ->where('status', 'external')
            ->where('is_external_confirmed', false)
            ->exists();

        // Only show eindstand on the 24th round of the season
        $isLastRound = $round->season_round_number === 24;

        // Previous/next completed rounds in the same season
        [$previousRound, $nextRound] = $this->adjacentRounds(
            $round,
            ['completed']
        );

        $allRounds = $this->seasonRounds($round->period->season_id);

        return view('uitslag', [
            'round' => $round,
            'pairings' => $pairings,
            'byePairing' => $byePairing,
            'standings' => $standings,
            'eindstand' => $eindstand,
            'voorlopig' => $voorlopig,
            'isLastRound' => $isLastRound,
            'previousRound' => $previousRound,
            'nextRound' => $nextRound,
            'allRounds' => $allRounds,
        ]);
    }

    /**
     * Redirect to the latest paired or completed round's indeling page.
     */
    public function indelingLatest(): RedirectResponse
    {
        $round = Round::whereIn('status', ['paired', 'completed'])
            ->orderByDesc('season_round_number')
            ->orderByDesc('id')
            ->first();

        if (! $round) {
            return redirect()->route('home');
        }

        return redirect()->route('indeling', $round);
    }

    /**
     * Redirect to the latest completed round's uitslag page.
     */
    public function uitslagLatest(): RedirectResponse
    {
        $round = Round::where('status', 'completed')
            ->orderByDesc('season_round_number')
            ->orderByDesc('id')
            ->first();

        if (! $round) {
            return redirect()->route('home');
        }

        return redirect()->route('uitslag', $round);
    }

    /**
     * Filter standings to only include players meeting the minimum participation threshold.
     * Renumbers positions to be contiguous.
     */
    private function filterMinDeelname($standings)
    {
        $minDeelname = (int) Setting::get('min_deelname', 7);

        $filtered = $standings->filter(fn ($s) => $s->games_played >= $minDeelname)->values();

        $filtered->each(function ($standing, $index) {
            $standing->position = $index + 1;
        });

        return $filtered;
    }

    /**
     * Find the previous and next rounds in the same season with matching statuses.
     *
     * @return array{0: Round|null, 1: Round|null}
     */
    private function adjacentRounds(Round $round, array $statuses): array
    {
        $seasonId = $round->period->season_id;

        $sameSeasonQuery = fn () => Round::whereHas('period', fn ($q) => $q->where('season_id', $seasonId))
            ->whereIn('status', $statuses);

        $previous = $sameSeasonQuery()
            ->where('season_round_number', '<', $round->season_round_number)
            ->orderByDesc('season_round_number')
            ->first();

        $next = $sameSeasonQuery()
            ->where('season_round_number', '>', $round->season_round_number)
            ->orderBy('season_round_number')
            ->first();

        return [$previous, $next];
    }

    /**
     * All rounds in the season, grouped by period (keyed by period_id), with period model attached.
     * Each period has a `rounds` collection. Rounds carry their own status.
     *
     * @return Collection<int, Period>
     */
    private function seasonRounds(int $seasonId): Collection
    {
        return Period::where('season_id', $seasonId)
            ->orderBy('number')
            ->with(['rounds' => fn ($q) => $q->orderBy('season_round_number')])
            ->get();
    }
}

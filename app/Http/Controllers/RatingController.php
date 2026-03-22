<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingController extends Controller
{
    public function index(): View
    {
        $spelersKnsb = User::where('is_active', true)
            ->where('show_knsb_rating', true)
            ->whereNotNull('knsb_relatienummer')
            ->whereNotNull('elo_rating')
            ->where('elo_rating', '!=', 1200)
            ->orderByDesc('elo_rating')
            ->get();

        return view('ratings.index', compact('spelersKnsb'));
    }

    public function show(User $speler): View
    {
        $heeftKnsbRating = $speler->elo_rating !== null && $speler->elo_rating !== 1200;
        $knsbPubliek = $speler->show_knsb_rating && $heeftKnsbRating;

        if (! $knsbPubliek) {
            abort(404);
        }

        $knsbRatings = $speler->eloRatings()
            ->whereIn('source', ['knsb', 'manual'])
            ->orderBy('measured_at')
            ->get();

        return view('ratings.show', compact('speler', 'knsbRatings'));
    }

    public function toggleShowRating(Request $request, string $type): RedirectResponse
    {
        $user = $request->user();

        $user->update(['show_knsb_rating' => ! $user->show_knsb_rating]);

        $status = $user->show_knsb_rating ? 'zichtbaar' : 'verborgen';

        return redirect()->route('dashboard')
            ->with('success', "Je KNSB-rating is nu {$status} op de publieke pagina.");
    }
}

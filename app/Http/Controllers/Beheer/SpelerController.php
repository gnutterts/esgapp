<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Jobs\ImportKnsbRatings;
use App\Models\EloRating;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpelerController extends Controller
{
    public function index(): View
    {
        $spelers = User::where('role', 'speler')
            ->orWhere('role', 'wedstrijdleider')
            ->orderBy('name')
            ->get();

        return view('beheer.spelers.index', compact('spelers'));
    }

    public function create(): View
    {
        return view('beheer.spelers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'elo_rating' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'knsb_relatienummer' => ['nullable', 'string', 'max:20', 'unique:users,knsb_relatienummer'],
            'auto_participate' => ['boolean'],
            'show_knsb_rating' => ['boolean'],
        ]);

        $eloRating = $validated['elo_rating'] ?? null;
        $knsbNummer = $validated['knsb_relatienummer'] ?? null;

        $speler = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'elo_rating' => $eloRating,
            'knsb_relatienummer' => $knsbNummer,
            'is_active' => true,
            'auto_participate' => $request->boolean('auto_participate'),
            'show_knsb_rating' => $request->boolean('show_knsb_rating'),
        ]);
        $speler->role = 'speler';
        $speler->save();

        if ($knsbNummer) {
            ImportKnsbRatings::dispatch($speler);

            return redirect()->route('beheer.spelers.index')
                ->with('success', "Speler '{$speler->name}' aangemaakt. KNSB-ratings worden op de achtergrond opgehaald.");
        }

        return redirect()->route('beheer.spelers.index')
            ->with('success', "Speler '{$speler->name}' aangemaakt.");
    }

    public function edit(User $speler): View
    {
        return view('beheer.spelers.edit', compact('speler'));
    }

    public function update(Request $request, User $speler): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$speler->id],
            'elo_rating' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'knsb_relatienummer' => ['nullable', 'string', 'max:20', 'unique:users,knsb_relatienummer,'.$speler->id],
            'auto_participate' => ['boolean'],
            'show_knsb_rating' => ['boolean'],
        ]);

        $newElo = $validated['elo_rating'] ?? null;
        $newKnsb = $validated['knsb_relatienummer'] ?? null;
        $oldKnsb = $speler->knsb_relatienummer;
        $oldElo = $speler->elo_rating;
        $knsbChanged = $newKnsb && $newKnsb !== $oldKnsb;

        // Track manual ELO change
        if ($newElo !== null && $newElo !== $oldElo) {
            EloRating::create([
                'user_id' => $speler->id,
                'rating' => $newElo,
                'source' => 'manual',
                'measured_at' => now()->toDateString(),
            ]);
        }

        $speler->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'elo_rating' => $newElo,
            'knsb_relatienummer' => $newKnsb,
            'auto_participate' => $request->boolean('auto_participate'),
            'show_knsb_rating' => $request->boolean('show_knsb_rating'),
        ]);

        if ($knsbChanged) {
            ImportKnsbRatings::dispatch($speler);

            return redirect()->route('beheer.spelers.index')
                ->with('success', "Speler '{$speler->name}' bijgewerkt. KNSB-ratings worden op de achtergrond opgehaald.");
        }

        return redirect()->route('beheer.spelers.index')
            ->with('success', "Speler '{$speler->name}' bijgewerkt.");
    }

    public function toggleActive(User $speler): RedirectResponse
    {
        $speler->update(['is_active' => ! $speler->is_active]);

        $status = $speler->is_active ? 'geactiveerd' : 'gedeactiveerd';

        return redirect()->route('beheer.spelers.index')
            ->with('success', "Speler '{$speler->name}' {$status}.");
    }
}

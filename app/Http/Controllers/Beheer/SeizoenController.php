<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeizoenController extends Controller
{
    public function index(): View
    {
        $seizoenen = Season::orderByDesc('start_date')->get();

        return view('beheer.seizoenen.index', compact('seizoenen'));
    }

    public function create(): View
    {
        return view('beheer.seizoenen.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        // Unset current season
        Season::where('is_current', true)->update(['is_current' => false]);

        $seizoen = Season::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => true,
        ]);

        // Create 4 periods: period 1 = swiss, periods 2-4 = keizer
        $pairingSystems = [
            1 => 'swiss',
            2 => 'keizer',
            3 => 'keizer',
            4 => 'keizer',
        ];

        foreach ($pairingSystems as $number => $system) {
            Period::create([
                'season_id' => $seizoen->id,
                'number' => $number,
                'pairing_system' => $system,
            ]);
        }

        return redirect()->route('beheer.seizoenen.index')
            ->with('success', "Seizoen '{$seizoen->name}' aangemaakt met 4 periodes.");
    }

    public function edit(Season $seizoen): View
    {
        return view('beheer.seizoenen.edit', compact('seizoen'));
    }

    public function update(Request $request, Season $seizoen): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $seizoen->update($validated);

        return redirect()->route('beheer.seizoenen.show', $seizoen)
            ->with('success', "Seizoen '{$seizoen->name}' bijgewerkt.");
    }

    public function show(Season $seizoen): View
    {
        $seizoen->load(['periods.rounds' => function ($query) {
            $query->orderBy('season_round_number');
        }]);

        return view('beheer.seizoenen.show', compact('seizoen'));
    }

    public function destroy(Season $seizoen): RedirectResponse
    {
        $naam = $seizoen->name;

        if ($seizoen->is_current) {
            $ouder = Season::where('id', '!=', $seizoen->id)
                ->orderByDesc('start_date')
                ->first();

            if ($ouder) {
                $ouder->update(['is_current' => true]);
            }
        }

        $seizoen->delete();

        return redirect()->route('beheer.seizoenen.index')
            ->with('success', "Seizoen '{$naam}' is verwijderd.");
    }
}

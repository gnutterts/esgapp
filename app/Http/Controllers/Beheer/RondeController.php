<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Pairing;
use App\Models\Registration;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\User;
use App\Services\KeizerPointsService;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RondeController extends Controller
{
    public function index(): View
    {
        $seizoen = Season::current()->with(['periods.rounds' => function ($query) {
            $query->orderBy('season_round_number');
        }])->first();

        return view('beheer.rondes.index', compact('seizoen'));
    }

    public function create(): View
    {
        $seizoen = Season::current()->with('periods.rounds')->first();
        $perioden = $seizoen ? $seizoen->periods->sortBy('number') : collect();

        return view('beheer.rondes.create', compact('seizoen', 'perioden'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => ['required', 'exists:periods,id'],
            'date' => ['required', 'date'],
            'registration_deadline' => ['nullable', 'date'],
        ]);

        $seizoen = Season::current()->firstOrFail();

        // Verify the chosen period belongs to the current season
        $period = $seizoen->periods()->where('id', $validated['period_id'])->firstOrFail();

        // Auto-calculate round_number within the period
        $roundNumber = $period->rounds()->count() + 1;

        // Auto-calculate season_round_number across all periods
        $seasonRoundNumber = Round::whereHas('period', function ($q) use ($seizoen) {
            $q->where('season_id', $seizoen->id);
        })->count() + 1;

        $ronde = Round::create([
            'period_id' => $period->id,
            'round_number' => $roundNumber,
            'season_round_number' => $seasonRoundNumber,
            'date' => $validated['date'],
            'registration_deadline' => $validated['registration_deadline'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()->route('beheer.rondes.show', $ronde)
            ->with('success', "Ronde {$seasonRoundNumber} aangemaakt (periode {$period->number}, ronde {$roundNumber}).");
    }

    public function show(Round $ronde): View
    {
        $ronde->load([
            'period.season',
            'registrations.user',
            'pairings.whitePlayer',
            'pairings.blackPlayer',
            'pairings.byePlayer',
            'roundPlayerStatuses.user',
        ]);

        // For scheduled rounds: show auto-participate users who don't have an explicit registration yet
        $autoDeelnameSpelers = collect();
        if ($ronde->status === 'scheduled') {
            $registeredUserIds = $ronde->registrations->pluck('user_id');
            $autoDeelnameSpelers = User::where('auto_participate', true)
                ->where('is_active', true)
                ->whereNotIn('id', $registeredUserIds)
                ->orderBy('name')
                ->get();
        }

        return view('beheer.rondes.show', compact('ronde', 'autoDeelnameSpelers'));
    }

    public function closeRegistration(Round $ronde): RedirectResponse
    {
        if ($ronde->status !== 'scheduled') {
            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Inschrijving kan alleen gesloten worden vanuit de status "Gepland".');
        }

        // Materialize auto-participate registrations: create 'available' for auto-participate
        // users who don't have an explicit registration (opt-out) for this round.
        $autoUsers = User::where('auto_participate', true)
            ->where('is_active', true)
            ->whereDoesntHave('registrations', function ($q) use ($ronde) {
                $q->where('round_id', $ronde->id);
            })
            ->get();

        foreach ($autoUsers as $user) {
            Registration::create([
                'round_id' => $ronde->id,
                'user_id' => $user->id,
                'status' => 'available',
            ]);
        }

        $ronde->update(['status' => 'registration_closed']);

        $message = 'Inschrijving gesloten.';
        if ($autoUsers->count() > 0) {
            $message .= " {$autoUsers->count()} speler(s) automatisch ingeschreven via auto-deelname.";
        }

        return redirect()->route('beheer.rondes.show', $ronde)
            ->with('success', $message);
    }

    public function generatePairing(Round $ronde): RedirectResponse
    {
        if ($ronde->status !== 'registration_closed') {
            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Indeling kan alleen gegenereerd worden wanneer de inschrijving gesloten is.');
        }

        try {
            $pairings = (new PairingService)->generatePairings($ronde);

            $count = $pairings->count();
            $byeCount = $pairings->where('is_bye', true)->count();
            $gameCount = $count - $byeCount;

            $message = "{$gameCount} partij(en) ingedeeld.";
            if ($byeCount > 0) {
                $message .= " {$byeCount} speler heeft een bye.";
            }

            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Fout bij genereren indeling', ['round_id' => $ronde->id, 'error' => $e->getMessage()]);

            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Er is een fout opgetreden bij het genereren van de indeling. Probeer het opnieuw.');
        }
    }

    public function finalizePairing(Round $ronde): RedirectResponse
    {
        if ($ronde->status !== 'registration_closed') {
            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Indeling kan alleen definitief gemaakt worden vanuit de status "Inschrijving gesloten".');
        }

        if ($ronde->pairings()->count() === 0) {
            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Er moet eerst een indeling gegenereerd worden.');
        }

        $ronde->update(['status' => 'paired']);

        return redirect()->route('beheer.rondes.show', $ronde)
            ->with('success', 'Indeling definitief gemaakt en zichtbaar voor spelers.');
    }

    public function showResults(Round $ronde): View
    {
        $ronde->load([
            'period.season',
            'pairings.whitePlayer',
            'pairings.blackPlayer',
            'pairings.byePlayer',
            'roundPlayerStatuses.user',
        ]);

        return view('beheer.rondes.results', compact('ronde'));
    }

    public function storeResults(Request $request, Round $ronde): RedirectResponse
    {
        $validated = $request->validate([
            'results' => ['array'],
            'results.*.result' => ['nullable', 'in:1-0,0-1,remise,*'],
            'player_status' => ['array'],
            'player_status.*.status' => ['nullable', 'in:played,absent,external,bye'],
            'player_status.*.is_external_confirmed' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $ronde) {
            // Save pairing results
            if (! empty($validated['results'])) {
                foreach ($validated['results'] as $pairingId => $data) {
                    $pairing = Pairing::where('id', $pairingId)
                        ->where('round_id', $ronde->id)
                        ->first();

                    if ($pairing && isset($data['result'])) {
                        $pairing->update(['result' => $data['result']]);
                    }
                }
            }

            // Save / update round_player_statuses
            if (! empty($validated['player_status'])) {
                foreach ($validated['player_status'] as $userId => $data) {
                    if (isset($data['status'])) {
                        $updateData = ['status' => $data['status']];

                        if ($data['status'] === 'external') {
                            $updateData['is_external_confirmed'] = ! empty($data['is_external_confirmed']);
                        } else {
                            $updateData['is_external_confirmed'] = false;
                        }

                        RoundPlayerStatus::updateOrCreate(
                            ['round_id' => $ronde->id, 'user_id' => $userId],
                            $updateData
                        );
                    }
                }
            }
        });

        return redirect()->route('beheer.rondes.show', $ronde)
            ->with('success', 'Resultaten opgeslagen.');
    }

    public function completeRound(Round $ronde): RedirectResponse
    {
        if ($ronde->status !== 'paired') {
            return redirect()->route('beheer.rondes.show', $ronde)
                ->with('error', 'Ronde kan alleen afgerond worden vanuit de status "Ingedeeld".');
        }

        $ronde->update(['status' => 'completed']);

        $season = $ronde->period->season;
        (new KeizerPointsService)->recalculateStandings($season);

        return redirect()->route('beheer.rondes.show', $ronde)
            ->with('success', 'Ronde afgerond. Standen zijn herberekend.');
    }

    public function editPairing(Round $ronde): View
    {
        $ronde->load([
            'period.season',
            'pairings.whitePlayer',
            'pairings.blackPlayer',
            'pairings.byePlayer',
            'registrations.user',
        ]);

        // All available (status=available) users for this round
        $registeredUsers = $ronde->registrations
            ->where('status', 'available')
            ->pluck('user')
            ->filter()
            ->sortBy('name')
            ->values();

        // Users already assigned in pairings (white, black, or bye)
        $assignedUserIds = collect();
        foreach ($ronde->pairings as $pairing) {
            if ($pairing->is_bye) {
                if ($pairing->bye_user_id) {
                    $assignedUserIds->push($pairing->bye_user_id);
                }
            } else {
                if ($pairing->white_user_id) {
                    $assignedUserIds->push($pairing->white_user_id);
                }
                if ($pairing->black_user_id) {
                    $assignedUserIds->push($pairing->black_user_id);
                }
            }
        }

        $availablePlayers = $registeredUsers->whereNotIn('id', $assignedUserIds->toArray())->values();
        $unavailablePlayers = $registeredUsers->whereIn('id', $assignedUserIds->toArray())->values();

        return view('beheer.rondes.edit-pairing', compact('ronde', 'registeredUsers', 'availablePlayers', 'unavailablePlayers'));
    }

    public function updatePairing(Request $request, Round $ronde): RedirectResponse
    {
        $validated = $request->validate([
            'pairings' => ['nullable', 'array'],
            'pairings.*.board_number' => ['required', 'integer', 'min:1'],
            'pairings.*.white_user_id' => ['nullable', 'exists:users,id'],
            'pairings.*.black_user_id' => ['nullable', 'exists:users,id'],
            'bye_user_id' => ['nullable', 'exists:users,id'],
        ]);

        // Validate no self-pairings
        if (! empty($validated['pairings'])) {
            foreach ($validated['pairings'] as $pairingData) {
                $whiteId = $pairingData['white_user_id'] ?? null;
                $blackId = $pairingData['black_user_id'] ?? null;
                if ($whiteId && $blackId && $whiteId == $blackId) {
                    return redirect()->route('beheer.rondes.edit-pairing', $ronde)
                        ->with('error', 'Een speler kan niet tegen zichzelf spelen.');
                }
            }
        }

        $createdCount = DB::transaction(function () use ($validated, $ronde) {
            // Delete all existing pairings for this round
            $ronde->pairings()->delete();

            $createdCount = 0;

            // Recreate normal pairings
            if (! empty($validated['pairings'])) {
                foreach ($validated['pairings'] as $pairingData) {
                    $whiteId = $pairingData['white_user_id'] ?? null;
                    $blackId = $pairingData['black_user_id'] ?? null;

                    // Skip completely empty rows
                    if (! $whiteId && ! $blackId) {
                        continue;
                    }

                    Pairing::create([
                        'round_id' => $ronde->id,
                        'board_number' => $pairingData['board_number'],
                        'white_user_id' => $whiteId,
                        'black_user_id' => $blackId,
                        'is_bye' => false,
                    ]);

                    $createdCount++;
                }
            }

            // Handle bye assignment
            if (! empty($validated['bye_user_id'])) {
                $maxBoard = $ronde->pairings()->max('board_number') ?? 0;

                Pairing::create([
                    'round_id' => $ronde->id,
                    'board_number' => $maxBoard + 1,
                    'is_bye' => true,
                    'bye_user_id' => $validated['bye_user_id'],
                ]);
            }

            return $createdCount;
        });

        return redirect()->route('beheer.rondes.edit-pairing', $ronde)
            ->with('success', "Indeling opgeslagen ({$createdCount} partij(en)).");
    }

    public function swapColors(Round $ronde, Pairing $pairing): RedirectResponse
    {
        if ($pairing->round_id !== $ronde->id || $pairing->is_bye) {
            return redirect()->route('beheer.rondes.edit-pairing', $ronde)
                ->with('error', 'Kleuren kunnen niet worden gewisseld voor deze partij.');
        }

        $pairing->update([
            'white_user_id' => $pairing->black_user_id,
            'black_user_id' => $pairing->white_user_id,
        ]);

        return redirect()->route('beheer.rondes.edit-pairing', $ronde)
            ->with('success', 'Kleuren gewisseld.');
    }
}

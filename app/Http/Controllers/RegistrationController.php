<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Round;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    /**
     * Toggle the authenticated user's registration for a round.
     *
     * Auto-participate users are virtually available (no registration needed).
     * - No registration + auto_participate: first click creates 'unavailable' (explicit opt-out).
     * - 'unavailable' + auto_participate: clicking DELETES registration (returns to virtual available).
     *
     * Non-auto-participate users follow the standard flow:
     * - No registration: first click creates 'available'.
     * - 'available' ↔ 'unavailable': toggle between states.
     */
    public function toggle(Round $round)
    {
        $user = Auth::user();

        // Guard: round must be scheduled
        if ($round->status !== 'scheduled') {
            return back()->with('error', 'Inschrijving is niet mogelijk voor deze ronde.');
        }

        // Guard: deadline must not have passed
        if ($round->registration_deadline && now()->isAfter($round->registration_deadline)) {
            return back()->with('error', 'De inschrijvingsdeadline voor deze ronde is verstreken.');
        }

        $registration = Registration::where('round_id', $round->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $registration) {
            if ($user->auto_participate) {
                // Auto-participate user opting OUT (virtually available → explicit opt-out)
                Registration::create([
                    'round_id' => $round->id,
                    'user_id' => $user->id,
                    'status' => 'unavailable',
                ]);

                return back()->with('success', 'Je bent afgemeld voor ronde '.$round->season_round_number.'.');
            }

            // Non-auto-participate user opting IN
            Registration::create([
                'round_id' => $round->id,
                'user_id' => $user->id,
                'status' => 'available',
            ]);

            return back()->with('success', 'Je bent ingeschreven als beschikbaar voor ronde '.$round->season_round_number.'.');
        }

        if ($registration->status === 'available') {
            $registration->update(['status' => 'unavailable']);

            return back()->with('success', 'Je beschikbaarheid voor ronde '.$round->season_round_number.' is gewijzigd naar niet beschikbaar.');
        }

        // Status is 'unavailable'
        if ($user->auto_participate) {
            // Delete registration to return to virtual available state
            $registration->delete();

            return back()->with('success', 'Je bent weer beschikbaar voor ronde '.$round->season_round_number.' (auto-deelname).');
        }

        $registration->update(['status' => 'available']);

        return back()->with('success', 'Je beschikbaarheid voor ronde '.$round->season_round_number.' is gewijzigd naar beschikbaar.');
    }

    /**
     * Toggle the authenticated user's auto_participate boolean.
     */
    public function toggleAutoParticipate()
    {
        $user = Auth::user();
        $user->update(['auto_participate' => ! $user->auto_participate]);

        $status = $user->auto_participate ? 'ingeschakeld' : 'uitgeschakeld';

        return back()->with('success', 'Auto-deelname is '.$status.'.');
    }
}

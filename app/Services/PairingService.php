<?php

namespace App\Services;

use App\Models\Pairing;
use App\Models\Round;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PairingService
{
    /**
     * Generate pairings for the given round.
     * Determines the correct engine based on the period's pairing_system
     * and delegates the pairing generation.
     *
     * @return \Illuminate\Support\Collection<Pairing>
     */
    public function generatePairings(Round $round): Collection
    {
        return DB::transaction(function () use ($round) {
            // Delete any existing pairings for this round
            Pairing::where('round_id', $round->id)->delete();

            // Determine which engine to use based on the period's pairing system
            $period = $round->period;
            $pairingSystem = $period->pairing_system;

            $engine = match ($pairingSystem) {
                'swiss'  => new SwissPairingEngine(),
                'keizer' => new KeizerPairingEngine(),
                default  => throw new \InvalidArgumentException("Unknown pairing system: {$pairingSystem}"),
            };

            return $engine->generatePairings($round);
        });
    }
}

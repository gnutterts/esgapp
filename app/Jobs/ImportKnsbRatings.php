<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\KnsbRatingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportKnsbRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 600;

    public function __construct(
        public User $user
    ) {}

    public function handle(KnsbRatingService $service): void
    {
        $relatienummer = $this->user->knsb_relatienummer;

        if (! $relatienummer) {
            return;
        }

        // Look up current rating if user has no ELO yet
        if ($this->user->elo_rating === null) {
            $rating = $service->lookupRating($relatienummer);
            if ($rating !== null) {
                $this->user->update(['elo_rating' => $rating]);
                Log::info("KNSB rating {$rating} opgehaald voor {$this->user->name} ({$relatienummer})");
            } else {
                $this->user->update(['elo_rating' => 1200]);
                Log::info("Geen KNSB-rating gevonden voor {$this->user->name} ({$relatienummer}), default 1200 ingesteld");
            }
        }

        // Import historical ratings
        $added = $service->importHistoricalRatings($this->user);
        Log::info("KNSB historische import voor {$this->user->name}: {$added} ratings toegevoegd");
    }
}

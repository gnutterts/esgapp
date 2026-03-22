<?php

namespace App\Console\Commands;

use App\Models\MagicLink;
use Illuminate\Console\Command;

class CleanExpiredMagicLinks extends Command
{
    protected $signature = 'magic-links:clean';

    protected $description = 'Verwijder verlopen en gebruikte inlogcodes';

    public function handle(): int
    {
        $deleted = MagicLink::where(function ($query) {
            $query->whereNotNull('used_at')
                ->orWhere('expires_at', '<', now());
        })->delete();

        $this->info("Verwijderd: {$deleted} inlogcode(s).");

        return self::SUCCESS;
    }
}

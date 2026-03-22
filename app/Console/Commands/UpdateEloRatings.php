<?php

namespace App\Console\Commands;

use App\Services\KnsbRatingService;
use Illuminate\Console\Command;

class UpdateEloRatings extends Command
{
    protected $signature = 'elo:update';

    protected $description = 'Update ELO-ratings vanuit de KNSB ratinglijst';

    public function handle(KnsbRatingService $service): int
    {
        if (! $service->hasNewVersion()) {
            $this->info('Geen nieuwe KNSB ratinglijst beschikbaar.');
            return self::SUCCESS;
        }

        $this->info('Nieuwe ratinglijst gevonden, bezig met bijwerken...');

        $result = $service->updateAllRatings();

        if (isset($result['error'])) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        $this->info("Bijgewerkt: {$result['updated']}, Ongewijzigd: {$result['unchanged']}, Niet gevonden: {$result['not_found']}");

        return self::SUCCESS;
    }
}

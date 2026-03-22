<?php

namespace App\Services;

use App\Models\EloRating;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class KnsbRatingService
{
    private string $storagePath = 'knsb';

    /**
     * Ensure the latest KNSB rating list is downloaded.
     * Returns the path to the CSV file.
     */
    public function ensureLatestDownloaded(): ?string
    {
        $now = now();
        $filename = $this->csvFilename($now->year, $now->month);
        $path = $this->storagePath.'/'.$filename;

        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        return $this->downloadArchive($now->year, $now->month);
    }

    /**
     * Download a specific month's rating archive.
     * Returns the full filesystem path to the CSV, or null on failure.
     */
    public function downloadArchive(int $year, int $month): ?string
    {
        $filename = $this->csvFilename($year, $month);
        $storagePath = $this->storagePath.'/'.$filename;

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        Storage::makeDirectory($this->storagePath);

        $mm = str_pad($month, 2, '0', STR_PAD_LEFT);
        $urls = [
            "https://schaakbond.nl/wp-content/uploads/{$year}/{$mm}/KLASSIEK.zip",
            "https://schaakbond.nl/wp-content/uploads/{$year}/{$mm}/{$year}-{$mm}-KLASSIEK.zip",
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(30)->get($url);

                if ($response->successful()) {
                    $csvContent = $this->extractCsvFromZip($response->body());
                    if ($csvContent !== null) {
                        Storage::put($storagePath, $csvContent);

                        return Storage::path($storagePath);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("KNSB download failed for {$url}: {$e->getMessage()}");
            }
        }

        Log::warning("Could not download KNSB rating list for {$year}-{$mm}");

        return null;
    }

    /**
     * Check if a newer version of the rating list is available.
     */
    public function hasNewVersion(): bool
    {
        $now = now();
        $filename = $this->csvFilename($now->year, $now->month);

        return ! Storage::exists($this->storagePath.'/'.$filename);
    }

    /**
     * Parse a KNSB CSV file into [relatienummer => rating].
     */
    public function parseRatingFile(string $csvPath): array
    {
        $ratings = [];

        if (! file_exists($csvPath)) {
            return $ratings;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return $ratings;
        }

        // Skip header line
        fgets($handle);

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $fields = str_getcsv($line, ';');
            if (count($fields) >= 5) {
                $relatienummer = trim($fields[0]);
                $rating = (int) trim($fields[4]);
                if ($relatienummer !== '' && $rating > 0) {
                    $ratings[$relatienummer] = $rating;
                }
            }
        }

        fclose($handle);

        return $ratings;
    }

    /**
     * Look up a single player's rating.
     */
    public function lookupRating(string $relatienummer, ?string $csvPath = null): ?int
    {
        if ($csvPath === null) {
            $csvPath = $this->ensureLatestDownloaded();
        }

        if ($csvPath === null) {
            return null;
        }

        $ratings = $this->parseRatingFile($csvPath);

        return $ratings[$relatienummer] ?? null;
    }

    /**
     * Update ratings for all users with a KNSB number.
     * Returns summary: ['updated' => int, 'unchanged' => int, 'not_found' => int]
     */
    public function updateAllRatings(): array
    {
        $csvPath = $this->ensureLatestDownloaded();

        if ($csvPath === null) {
            return ['updated' => 0, 'unchanged' => 0, 'not_found' => 0, 'error' => 'Kon ratinglijst niet downloaden'];
        }

        $ratings = $this->parseRatingFile($csvPath);
        $users = User::whereNotNull('knsb_relatienummer')->get();

        $updated = 0;
        $unchanged = 0;
        $notFound = 0;

        foreach ($users as $user) {
            $newRating = $ratings[$user->knsb_relatienummer] ?? null;

            if ($newRating === null) {
                $notFound++;

                continue;
            }

            if ((int) $user->elo_rating === $newRating) {
                $unchanged++;

                continue;
            }

            $user->update(['elo_rating' => $newRating]);

            EloRating::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'measured_at' => now()->startOfMonth(),
                    'source' => 'knsb',
                ],
                [
                    'rating' => $newRating,
                ]
            );

            $updated++;
        }

        return ['updated' => $updated, 'unchanged' => $unchanged, 'not_found' => $notFound];
    }

    /**
     * Import historical ratings for a player from Feb 2023 to now.
     * Returns the number of records added.
     */
    public function importHistoricalRatings(User $user): int
    {
        if (! $user->knsb_relatienummer) {
            return 0;
        }

        $added = 0;
        $startYear = 2023;
        $startMonth = 2;
        $now = now();

        for ($year = $startYear; $year <= $now->year; $year++) {
            $monthStart = ($year === $startYear) ? $startMonth : 1;
            $monthEnd = ($year === $now->year) ? $now->month : 12;

            for ($month = $monthStart; $month <= $monthEnd; $month++) {
                $measuredAt = sprintf('%04d-%02d-01', $year, $month);

                // Skip if we already have a record for this month
                $exists = EloRating::where('user_id', $user->id)
                    ->where('measured_at', $measuredAt)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $csvPath = $this->downloadArchive($year, $month);
                if ($csvPath === null) {
                    continue;
                }

                $ratings = $this->parseRatingFile($csvPath);
                $rating = $ratings[$user->knsb_relatienummer] ?? null;

                if ($rating !== null) {
                    EloRating::create([
                        'user_id' => $user->id,
                        'rating' => $rating,
                        'source' => 'knsb',
                        'measured_at' => $measuredAt,
                    ]);
                    $added++;
                }
            }
        }

        return $added;
    }

    private function csvFilename(int $year, int $month): string
    {
        $mm = str_pad($month, 2, '0', STR_PAD_LEFT);

        return "KLASSIEK-{$year}-{$mm}.csv";
    }

    private function extractCsvFromZip(string $zipContent): ?string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'knsb_');
        file_put_contents($tmpFile, $zipContent);

        $zip = new ZipArchive;
        if ($zip->open($tmpFile) !== true) {
            unlink($tmpFile);

            return null;
        }

        $csvContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtoupper($name), '.CSV')) {
                $csvContent = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();
        unlink($tmpFile);

        return $csvContent ?: null;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Pairing;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\Standing;
use App\Models\User;
use App\Services\KeizerPointsService;
use App\Services\PairingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SimulateSeizoen extends Command
{
    protected $signature = 'seizoen:simuleer {--seizoen= : Season ID to simulate (auto-detected if omitted)} {--force : Sta uitvoering toe buiten lokale omgeving (alleen voor initiële seizoensopbouw)}';

    protected $description = 'Simuleer 20 rondes voor het testseizoen met realistische ELO-gewogen resultaten';

    // Mid-season joiners: user_id => season_round_number they join at
    private const LATE_JOINERS = [
        9 => 7,   // Jarno Koopman — start periode 2
        8 => 13,  // Frank Kieft   — start periode 3
    ];

    // Rounds per period
    private const ROUNDS_PER_PERIOD = 6;

    // Total rounds to simulate (completed)
    private const TOTAL_ROUNDS = 20;

    // Planned (scheduled) rounds to add after the completed rounds
    private const PLANNED_ROUNDS = 3;

    // Period IDs for season 593
    // period_number => period_id (set dynamically)
    private array $periodMap = [];

    public function handle(): int
    {
        // SAFETY GUARD: this command truncates data and must never run in production
        if (! in_array(app()->environment(), ['local', 'testing']) && ! $this->option('force')) {
            $this->error('Dit commando mag alleen in een lokale of testomgeving worden uitgevoerd. Gebruik --force om toch door te gaan.');

            return 1;
        }

        $seizoenId = $this->option('seizoen')
            ? (int) $this->option('seizoen')
            : Season::orderByDesc('id')->value('id');

        $seizoen = Season::with('periods')->find($seizoenId);

        if (! $seizoen) {
            $this->error("Seizoen {$seizoenId} niet gevonden.");

            return 1;
        }

        // Mark this season as the current season (mirrors SeizoenController::store logic)
        Season::where('is_current', true)->where('id', '!=', $seizoen->id)->update(['is_current' => false]);
        $seizoen->update(['is_current' => true]);

        $this->info("=== Simulatie: {$seizoen->name} ===");
        $this->info('');

        // Build period map: number => Period model
        foreach ($seizoen->periods->sortBy('number') as $period) {
            $this->periodMap[$period->number] = $period;
        }

        // Ensure late joiners start with auto_participate=false
        User::whereIn('id', array_keys(self::LATE_JOINERS))->update([
            'auto_participate' => false,
            'joined_at_round_id' => null,
        ]);

        // All active players
        $allPlayers = User::where('is_active', true)->orderBy('name')->get();
        $this->info("Totaal actieve spelers: {$allPlayers->count()}");

        // Starting date: 15 September 2025, every 2 weeks
        $startDate = Carbon::create(2025, 9, 15);
        $seasonRoundNr = 0;

        $createdRounds = collect();

        for ($roundNr = 1; $roundNr <= self::TOTAL_ROUNDS; $roundNr++) {
            $seasonRoundNr++;
            $periodNumber = (int) ceil($roundNr / self::ROUNDS_PER_PERIOD);
            $periodNumber = min($periodNumber, 4); // cap at 4
            $roundInPeriod = (($roundNr - 1) % self::ROUNDS_PER_PERIOD) + 1;
            $period = $this->periodMap[$periodNumber];

            $roundDate = $startDate->copy()->addWeeks(($roundNr - 1) * 2);

            $this->line("--- Periode {$periodNumber}, ronde {$roundInPeriod} ({$period->pairing_system}) — {$roundDate->format('d-m-Y')} ---");

            // 1. Create round
            $ronde = Round::create([
                'period_id' => $period->id,
                'round_number' => $roundInPeriod,
                'season_round_number' => $seasonRoundNr,
                'date' => $roundDate->toDateString(),
                'registration_deadline' => null,
                'status' => 'scheduled',
            ]);
            $createdRounds->push($ronde);

            // 2. Handle mid-season joiners: set joined_at_round_id on their first round
            foreach (self::LATE_JOINERS as $userId => $joinAtRoundNr) {
                if ($roundNr === $joinAtRoundNr) {
                    User::where('id', $userId)->update(['joined_at_round_id' => $ronde->id]);
                    $name = $allPlayers->firstWhere('id', $userId)?->name ?? $userId;
                    $this->line("  -> {$name} voegt toe aan competitie (joined_at_round_id = {$ronde->id})");
                }
            }

            // 3. Determine which players are eligible this round
            $eligible = $allPlayers->filter(function ($player) use ($roundNr) {
                foreach (self::LATE_JOINERS as $userId => $joinAt) {
                    if ($player->id === $userId && $roundNr < $joinAt) {
                        return false;
                    }
                }

                return true;
            });

            // 4. Determine attendance: random between 14 and min(20, eligible count)
            // Weighted toward 16-18: sample from a range biased to the middle
            $minAttend = min(14, $eligible->count());
            $maxAttend = min(20, $eligible->count());
            $attendance = $this->randomAttendance($minAttend, $maxAttend);

            // Sample $attendance players randomly from eligible
            $attending = $eligible->shuffle()->take($attendance);

            // 4b. Separate ~10% of attendees as external game players
            // They count as present but play an external game instead — removed from pairing pool
            $externalCount = (int) round($attending->count() * 0.10);
            $externalPlayers = $attending->shuffle()->take($externalCount);
            $chessPlayers = $attending->diff($externalPlayers);

            $this->line("  Aanwezigen: {$attending->count()} van {$eligible->count()} spelers".
                ($externalPlayers->count() ? " ({$externalPlayers->count()} extern)" : ''));

            // 5a. Create RoundPlayerStatus 'external' for external players
            foreach ($externalPlayers as $player) {
                RoundPlayerStatus::create([
                    'round_id' => $ronde->id,
                    'user_id' => $player->id,
                    'status' => 'external',
                    'is_external_confirmed' => true,
                ]);
            }

            // 5b. Register chess players (the pairing pool)
            foreach ($chessPlayers as $player) {
                Registration::create([
                    'round_id' => $ronde->id,
                    'user_id' => $player->id,
                    'status' => 'available',
                ]);
            }

            // 5c. Create RoundPlayerStatus 'absent' for non-attending eligible players
            // Also register auto-participate players as 'unavailable' so the safety net
            // in getAvailablePlayers() does not accidentally include absent players.
            $absentPlayers = $eligible->diff($attending);
            foreach ($absentPlayers as $player) {
                RoundPlayerStatus::create([
                    'round_id' => $ronde->id,
                    'user_id' => $player->id,
                    'status' => 'absent',
                    'is_external_confirmed' => false,
                ]);
                // Prevent safety net from including absent auto-participate players
                if ($player->auto_participate) {
                    Registration::create([
                        'round_id' => $ronde->id,
                        'user_id' => $player->id,
                        'status' => 'unavailable',
                    ]);
                }
            }

            // 6. Close registration (status → registration_closed)
            $ronde->update(['status' => 'registration_closed']);

            // 7. Generate pairings
            try {
                $pairings = (new PairingService)->generatePairings($ronde);
            } catch (\Exception $e) {
                $this->error('  FOUT bij indeling genereren: '.$e->getMessage());

                return 1;
            }

            $gameCount = $pairings->where('is_bye', false)->count();
            $byeCount = $pairings->where('is_bye', true)->count();
            $this->line("  Indeling: {$gameCount} partijen".($byeCount ? ", {$byeCount} bye" : ''));

            // 7b. Create RoundPlayerStatus 'bye' for the bye player (if any)
            $byePairing = $pairings->firstWhere('is_bye', true);
            if ($byePairing) {
                $byeUserId = $byePairing->bye_user_id;
                if ($byeUserId) {
                    // Only create bye status if player doesn't already have a status for this round
                    $existingStatus = RoundPlayerStatus::where('round_id', $ronde->id)
                        ->where('user_id', $byeUserId)
                        ->first();
                    if (! $existingStatus) {
                        RoundPlayerStatus::create([
                            'round_id' => $ronde->id,
                            'user_id' => $byeUserId,
                            'status' => 'bye',
                            'is_external_confirmed' => false,
                        ]);
                    }
                }
            }

            // 8. Finalize pairings (status → paired)
            $ronde->update(['status' => 'paired']);

            // 9. Assign ELO-weighted results
            $ronde->load('pairings');
            $this->assignResults($ronde);

            // 10. Complete round (status → completed, triggers standings recalc)
            $ronde->update(['status' => 'completed']);
            (new KeizerPointsService)->recalculateStandings($seizoen);

            // Only print standings at the very end
            if ($roundNr === self::TOTAL_ROUNDS) {
                $this->printStandings($ronde, $seizoen, 'Eindstand');
            }
        }

        // Add planned (scheduled) rounds after the completed ones
        $plannedRounds = collect();
        $period4 = $this->periodMap[4];
        $period4RoundCount = $period4->rounds()->count(); // already has rounds 19-20

        for ($i = 1; $i <= self::PLANNED_ROUNDS; $i++) {
            $plannedRoundNr = self::TOTAL_ROUNDS + $i;
            $roundInPeriod = $period4RoundCount + $i;
            $seasonRoundNr++;
            $roundDate = $startDate->copy()->addWeeks(($plannedRoundNr - 1) * 2);

            $ronde = Round::create([
                'period_id' => $period4->id,
                'round_number' => $roundInPeriod,
                'season_round_number' => $seasonRoundNr,
                'date' => $roundDate->toDateString(),
                'registration_deadline' => null,
                'status' => 'scheduled',
            ]);
            $plannedRounds->push($ronde);

            $this->line("--- Periode 4, ronde {$roundInPeriod} (gepland) — {$roundDate->format('d-m-Y')} ---");
        }

        $this->info('');
        $this->info('=== Simulatie voltooid ===');
        $this->info('Voltooide rondes: '.$createdRounds->count());
        $this->info('Geplande rondes:  '.$plannedRounds->count());
        $this->info('Seizoen: '.$seizoen->name);

        // Final integrity check
        $this->integrityCheck($seizoen);

        return 0;
    }

    /**
     * Random attendance biased toward the middle of the range.
     * Takes the average of two random values (triangular distribution approximation).
     */
    private function randomAttendance(int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }
        $a = rand($min, $max);
        $b = rand($min, $max);
        $avg = (int) round(($a + $b) / 2);
        // Make even number more likely (avoids bye in most rounds)
        // If odd, randomly nudge up or down (staying within bounds)
        if ($avg % 2 === 1) {
            $nudge = rand(0, 1) === 0 ? -1 : 1;
            $avg = max($min, min($max, $avg + $nudge));
        }

        return $avg;
    }

    /**
     * Assign ELO-weighted results to all pairings in the round.
     * Uses logistic ELO formula: P(white wins) = 1 / (1 + 10^((Eblack - Ewhite) / 400))
     * ~15% of expected decisive results become draws.
     */
    private function assignResults(Round $ronde): void
    {
        foreach ($ronde->pairings as $pairing) {
            if ($pairing->is_bye) {
                // No result needed for bye
                continue;
            }

            if (! $pairing->white_user_id || ! $pairing->black_user_id) {
                continue;
            }

            $whiteElo = User::find($pairing->white_user_id)?->elo_rating ?? 1200;
            $blackElo = User::find($pairing->black_user_id)?->elo_rating ?? 1200;

            $result = $this->eloWeightedResult($whiteElo, $blackElo);
            $pairing->update(['result' => $result]);
        }
    }

    /**
     * Generate a result string ('1-0', '0-1', 'remise') based on ELO difference.
     */
    private function eloWeightedResult(int $whiteElo, int $blackElo): string
    {
        // Probability white wins (logistic)
        $pWhite = 1.0 / (1.0 + pow(10, ($blackElo - $whiteElo) / 400.0));
        $pBlack = 1.0 - $pWhite;

        // ~15% draw chance, slightly reduced from decisive probability
        // Draw probability: higher when players are close in strength
        $eloDiff = abs($whiteElo - $blackElo);
        $drawBase = 0.30; // base draw probability when equal
        $drawDecay = $eloDiff / 1000.0; // reduces with larger ELO gap
        $pDraw = max(0.05, $drawBase - $drawDecay);

        // Redistribute: pWhiteWin + pDraw + pBlackWin = 1
        $pWhiteWin = $pWhite * (1 - $pDraw);
        $pBlackWin = $pBlack * (1 - $pDraw);

        $r = mt_rand() / mt_getrandmax();

        if ($r < $pWhiteWin) {
            return '1-0';
        } elseif ($r < $pWhiteWin + $pDraw) {
            return 'remise';
        } else {
            return '0-1';
        }
    }

    /**
     * Print standings table after a given round.
     */
    private function printStandings(Round $ronde, Season $seizoen, string $title): void
    {
        $standings = Standing::where('round_id', $ronde->id)
            ->with('user')
            ->orderBy('position')
            ->get();

        if ($standings->isEmpty()) {
            $this->warn("  Geen stand beschikbaar na ronde {$ronde->season_round_number}");

            return;
        }

        $this->info('');
        $this->info("  [{$title}]");
        $this->line(sprintf(
            '  %-4s %-24s %7s %5s %5s %5s %5s %5s',
            'Pos', 'Naam', 'Punten', 'Part', 'W', 'R', 'V', 'Afw'
        ));
        $this->line('  '.str_repeat('-', 65));

        foreach ($standings as $s) {
            $name = $s->user?->name ?? '(onbekend)';
            $this->line(sprintf(
                '  %-4s %-24s %7.1f %5d %5d %5d %5d %5d',
                $s->position,
                substr($name, 0, 24),
                $s->points,
                $s->games_played,
                $s->wins,
                $s->draws,
                $s->losses,
                $s->absence_count
            ));
        }
        $this->info('');
    }

    /**
     * Basic integrity checks after simulation.
     */
    private function integrityCheck(Season $season): void
    {
        $this->info('--- Integriteitcheck ---');

        $rounds = Round::whereHas('period', fn ($q) => $q->where('season_id', $season->id))
            ->where('status', 'completed')
            ->get();

        $plannedCount = Round::whereHas('period', fn ($q) => $q->where('season_id', $season->id))
            ->where('status', 'scheduled')
            ->count();

        $this->line("Voltooide rondes: {$rounds->count()}");
        $this->line("Geplande rondes:  {$plannedCount}");

        $totalPairings = Pairing::whereIn('round_id', $rounds->pluck('id'))->where('is_bye', false)->count();
        $totalByes = Pairing::whereIn('round_id', $rounds->pluck('id'))->where('is_bye', true)->count();
        $this->line("Totaal partijen: {$totalPairings}");
        $this->line("Totaal byes: {$totalByes}");

        $totalAbsent = RoundPlayerStatus::whereIn('round_id', $rounds->pluck('id'))->where('status', 'absent')->count();
        $totalExternal = RoundPlayerStatus::whereIn('round_id', $rounds->pluck('id'))->where('status', 'external')->count();
        $totalByeStatus = RoundPlayerStatus::whereIn('round_id', $rounds->pluck('id'))->where('status', 'bye')->count();
        $this->line("Totaal afwezigen (status): {$totalAbsent}");
        $this->line("Totaal extern (status): {$totalExternal}");
        $this->line("Totaal bye (status): {$totalByeStatus}");

        $unresolved = Pairing::whereIn('round_id', $rounds->pluck('id'))
            ->where('is_bye', false)
            ->whereNull('result')
            ->count();
        if ($unresolved > 0) {
            $this->warn("  WAARSCHUWING: {$unresolved} partijen zonder resultaat!");
        } else {
            $this->line('Alle partijen hebben een resultaat: OK');
        }

        // Check standings exist for last round
        $lastRound = $rounds->sortByDesc('season_round_number')->first();
        if ($lastRound) {
            $standingCount = Standing::where('round_id', $lastRound->id)->count();
            $this->line("Standen na ronde {$lastRound->season_round_number}: {$standingCount} spelers");
        }

        // Check late joiners
        foreach (self::LATE_JOINERS as $userId => $joinAt) {
            $user = User::find($userId);
            if ($user && $user->joined_at_round_id) {
                $this->line("Late inschrijver OK: {$user->name} (join ronde {$joinAt})");
            } else {
                $this->warn("  WAARSCHUWING: Late inschrijver {$userId} heeft geen joined_at_round_id!");
            }
        }

        $this->info('Integriteitcheck gereed.');
    }
}

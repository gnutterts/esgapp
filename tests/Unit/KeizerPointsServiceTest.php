<?php

namespace Tests\Unit;

use App\Models\Pairing;
use App\Models\Period;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Standing;
use App\Models\User;
use App\Services\KeizerPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeizerPointsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KeizerPointsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Keizer configuration settings
        Setting::set('max_waardering', 60);
        Setting::set('punten_extern', 40);
        Setting::set('punten_afwezig', 20);
        Setting::set('punten_oneven', 40);
        Setting::set('max_afwezig', 5);
        Setting::set('punten_nieuwe_speler', 15);
        Setting::set('factor_winst', 1);
        Setting::set('factor_remise', 0.5);
        Setting::set('factor_verlies', 0);

        $this->service = new KeizerPointsService();
    }

    /**
     * Create a season with one period and optionally some rounds.
     */
    protected function createSeasonWithPeriod(int $roundCount = 0): array
    {
        $season = Season::create([
            'name'       => 'Test Season',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
            'is_current' => true,
        ]);

        $period = Period::create([
            'season_id'      => $season->id,
            'number'         => 1,
            'pairing_system' => 'keizer',
        ]);

        $rounds = [];
        for ($i = 1; $i <= $roundCount; $i++) {
            $rounds[] = Round::create([
                'period_id'             => $period->id,
                'round_number'          => $i,
                'season_round_number'   => $i,
                'date'                  => '2025-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'status'                => 'completed',
                'registration_deadline' => '2025-01-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 18:00:00',
            ]);
        }

        return [$season, $period, $rounds];
    }

    /**
     * Create a user with an initial ranking.
     */
    protected function createPlayer(int $eloRating = null, string $name = null): User
    {
        $user = new User([
            'name'            => $name ?? 'Player ' . $eloRating,
            'email'           => fake()->unique()->safeEmail(),
            'is_active'       => true,
            'auto_participate' => false,
            'elo_rating'      => $eloRating,
        ]);
        $user->role = 'speler';
        $user->save();

        return $user;
    }

    public function test_initial_rank_values_based_on_elo_rating(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        // Create players with ELO ratings — higher ELO = higher rank value
        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');
        $player3 = $this->createPlayer(1400, 'Carol');

        // Add them to the round via pairings so getInitialRankValues finds them
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
        ]);
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 2,
            'white_user_id' => null,
            'black_user_id' => null,
            'is_bye'        => true,
            'bye_user_id'   => $player3->id,
        ]);

        $rankValues = $this->service->getInitialRankValues($season);

        // Highest ELO (1800) gets max_waardering (60), then 59, then 58
        $this->assertEquals(60, $rankValues[$player1->id]);
        $this->assertEquals(59, $rankValues[$player2->id]);
        $this->assertEquals(58, $rankValues[$player3->id]);
    }

    public function test_higher_elo_gets_higher_rank_value(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $strongPlayer = $this->createPlayer(2000, 'Strong');
        $weakPlayer = $this->createPlayer(1000, 'Weak');
        $noEloPlayer = $this->createPlayer(null, 'NoElo');

        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $strongPlayer->id,
            'black_user_id' => $weakPlayer->id,
            'is_bye'        => false,
        ]);
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 2,
            'white_user_id' => null,
            'black_user_id' => null,
            'is_bye'        => true,
            'bye_user_id'   => $noEloPlayer->id,
        ]);

        $rankValues = $this->service->getInitialRankValues($season);

        // 2000 > 1200 (default) > 1000
        $this->assertGreaterThan($rankValues[$noEloPlayer->id], $rankValues[$strongPlayer->id]);
        $this->assertGreaterThan($rankValues[$weakPlayer->id], $rankValues[$noEloPlayer->id]);
    }

    public function test_win_gives_opponent_rank_value_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        // Alice (rank 1 = value 60) vs Bob (rank 2 = value 59)
        // Alice wins as white
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
            'result'        => '1-0',
        ]);

        $rankValues = $this->service->getInitialRankValues($season);
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        // Winner gets opponent's rank value: Alice wins, she gets Bob's rank value (59)
        $this->assertEquals(59.0, $roundPoints[$player1->id]['points']);
        $this->assertEquals('win', $roundPoints[$player1->id]['type']);

        // Loser gets 0 (factor_verlies = 0)
        $this->assertEquals(0.0, $roundPoints[$player2->id]['points']);
        $this->assertEquals('loss', $roundPoints[$player2->id]['type']);
    }

    public function test_draw_gives_half_opponent_rank_value(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
            'result'        => 'remise',
        ]);

        $rankValues = $this->service->getInitialRankValues($season);
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        // Each gets 0.5 * opponent's rank value
        // Alice (rank 1, value 60) vs Bob (rank 2, value 59)
        // Alice gets 0.5 * 59 = 29.5
        $this->assertEquals(29.5, $roundPoints[$player1->id]['points']);
        $this->assertEquals('draw', $roundPoints[$player1->id]['type']);

        // Bob gets 0.5 * 60 = 30.0
        $this->assertEquals(30.0, $roundPoints[$player2->id]['points']);
        $this->assertEquals('draw', $roundPoints[$player2->id]['type']);
    }

    public function test_loss_gives_zero_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        // Alice loses as white
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
            'result'        => '0-1',
        ]);

        $rankValues = $this->service->getInitialRankValues($season);
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        // Loser (Alice, white) gets 0 points
        $this->assertEquals(0.0, $roundPoints[$player1->id]['points']);
        $this->assertEquals('loss', $roundPoints[$player1->id]['type']);
    }

    public function test_external_gives_40_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');

        RoundPlayerStatus::create([
            'round_id' => $round->id,
            'user_id'  => $player1->id,
            'status'   => 'external',
        ]);

        $rankValues = [$player1->id => 60];
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        $this->assertEquals(40.0, $roundPoints[$player1->id]['points']);
        $this->assertEquals('external', $roundPoints[$player1->id]['type']);
    }

    public function test_absence_gives_20_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');

        RoundPlayerStatus::create([
            'round_id' => $round->id,
            'user_id'  => $player1->id,
            'status'   => 'absent',
        ]);

        $rankValues = [$player1->id => 60];
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        $this->assertEquals(20.0, $roundPoints[$player1->id]['points']);
        $this->assertEquals('absent', $roundPoints[$player1->id]['type']);
    }

    public function test_absence_over_5_gives_zero_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(6);

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        // Add a pairing with a second player in each round so the season player detection works.
        // Mark player1 as absent in all 6 rounds.
        foreach ($rounds as $round) {
            RoundPlayerStatus::create([
                'round_id' => $round->id,
                'user_id'  => $player1->id,
                'status'   => 'absent',
            ]);
            // Also add player2 so they appear in the season
            RoundPlayerStatus::create([
                'round_id' => $round->id,
                'user_id'  => $player2->id,
                'status'   => 'absent',
            ]);
        }

        // Recalculate standings for the full season
        $season = $rounds[0]->period->season;
        $this->service->recalculateStandings($season);

        // After 6 rounds, standings for round 6 should show 0 for Alice (6th absence)
        // and at most 5 * 20 = 100 points (absence 6 gives 0)
        $standingRound6 = Standing::where('round_id', $rounds[5]->id)
            ->where('user_id', $player1->id)
            ->first();

        $this->assertNotNull($standingRound6);
        // 5 absences * 20 = 100 points, 6th absence = 0, total = 100
        $this->assertEquals(100.0, (float) $standingRound6->points);
    }

    public function test_bye_gives_40_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(1);
        $round = $rounds[0];

        $player1 = $this->createPlayer(1800, 'Alice');

        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => null,
            'black_user_id' => null,
            'is_bye'        => true,
            'bye_user_id'   => $player1->id,
        ]);

        $rankValues = [$player1->id => 60];
        $roundPoints = $this->service->calculateRoundPoints($round, $rankValues);

        $this->assertEquals(40.0, $roundPoints[$player1->id]['points']);
        $this->assertEquals('bye', $roundPoints[$player1->id]['type']);
    }

    public function test_mid_season_joiner_gets_starting_points(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(3);

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        // Bob joins at round 3 (season_round_number = 3)
        $player2->update(['joined_at_round_id' => $rounds[2]->id]);

        // Both players appear in round 3
        Pairing::create([
            'round_id'      => $rounds[2]->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
            'result'        => '1-0',
        ]);

        // Alice also appears in rounds 1 and 2
        RoundPlayerStatus::create([
            'round_id' => $rounds[0]->id,
            'user_id'  => $player1->id,
            'status'   => 'absent',
        ]);
        RoundPlayerStatus::create([
            'round_id' => $rounds[1]->id,
            'user_id'  => $player1->id,
            'status'   => 'absent',
        ]);

        $this->service->recalculateStandings($season);

        // Bob joined at round 3: missed 2 rounds, gets 2 * 15 = 30 starting points
        // Plus his result in round 3: lost (0 points)
        $standingRound3 = Standing::where('round_id', $rounds[2]->id)
            ->where('user_id', $player2->id)
            ->first();

        $this->assertNotNull($standingRound3);
        $this->assertEquals(30.0, (float) $standingRound3->points);
    }

    public function test_full_recalculation_across_multiple_rounds(): void
    {
        [$season, $period, $rounds] = $this->createSeasonWithPeriod(2);

        $player1 = $this->createPlayer(1800, 'Alice');
        $player2 = $this->createPlayer(1600, 'Bob');

        // Round 1: Alice (white, rank value 60) beats Bob (black, rank value 59)
        Pairing::create([
            'round_id'      => $rounds[0]->id,
            'board_number'  => 1,
            'white_user_id' => $player1->id,
            'black_user_id' => $player2->id,
            'is_bye'        => false,
            'result'        => '1-0',
        ]);

        // Round 2: Bob (white) beats Alice (black)
        Pairing::create([
            'round_id'      => $rounds[1]->id,
            'board_number'  => 1,
            'white_user_id' => $player2->id,
            'black_user_id' => $player1->id,
            'is_bye'        => false,
            'result'        => '1-0',
        ]);

        $this->service->recalculateStandings($season);

        // After round 1: Alice has 59 pts (won against Bob's rank value 59), Bob has 0
        $standingR1Alice = Standing::where('round_id', $rounds[0]->id)
            ->where('user_id', $player1->id)
            ->first();
        $this->assertNotNull($standingR1Alice);
        $this->assertEquals(59.0, (float) $standingR1Alice->points);
        $this->assertEquals(1, $standingR1Alice->position);

        // After round 2: rank values from R1 standings: Alice (1st=60), Bob (2nd=59)
        // Bob wins and gets Alice's rank value (60), total = 60
        // Alice had 59, loses 0, total = 59
        $standingR2Bob = Standing::where('round_id', $rounds[1]->id)
            ->where('user_id', $player2->id)
            ->first();
        $this->assertNotNull($standingR2Bob);
        $this->assertEquals(60.0, (float) $standingR2Bob->points);
        $this->assertEquals(1, $standingR2Bob->position);

        $standingR2Alice = Standing::where('round_id', $rounds[1]->id)
            ->where('user_id', $player1->id)
            ->first();
        $this->assertNotNull($standingR2Alice);
        $this->assertEquals(59.0, (float) $standingR2Alice->points);
        $this->assertEquals(2, $standingR2Alice->position);
    }
}

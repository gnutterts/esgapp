<?php

namespace Tests\Unit;

use App\Models\Pairing;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Round;
use App\Models\RoundPlayerStatus;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Standing;
use App\Models\User;
use App\Services\SwissPairingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwissPairingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected SwissPairingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('max_waardering', 60);
        Setting::set('punten_extern', 40);
        Setting::set('punten_afwezig', 20);
        Setting::set('punten_oneven', 40);
        Setting::set('max_afwezig', 5);
        Setting::set('punten_nieuwe_speler', 15);
        Setting::set('factor_winst', 1);
        Setting::set('factor_remise', 0.5);
        Setting::set('factor_verlies', 0);

        $this->engine = new SwissPairingEngine;
    }

    /**
     * Create a season with one period and a single round.
     */
    protected function createSeasonWithRound(string $status = 'registration_closed'): array
    {
        $season = Season::create([
            'name' => 'Test Season',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $period = Period::create([
            'season_id' => $season->id,
            'number' => 1,
            'pairing_system' => 'swiss',
        ]);

        $round = Round::create([
            'period_id' => $period->id,
            'round_number' => 1,
            'season_round_number' => 1,
            'date' => '2025-01-07',
            'status' => $status,
            'registration_deadline' => '2025-01-06 18:00:00',
        ]);

        return [$season, $period, $round];
    }

    /**
     * Create an active player and register them as available for a round.
     */
    protected function createPlayerUser(string $name, string $email): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'is_active' => true,
            'auto_participate' => false,
        ]);
        $user->role = 'speler';
        $user->save();

        return $user;
    }

    protected function createAndRegisterPlayer(Round $round, ?int $initialRanking = null, ?float $standingPoints = null, ?Round $previousRound = null): User
    {
        $player = new User([
            'name' => fake()->unique()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'auto_participate' => false,
            'elo_rating' => $initialRanking,
        ]);
        $player->role = 'speler';
        $player->save();

        Registration::create([
            'round_id' => $round->id,
            'user_id' => $player->id,
            'status' => 'available',
        ]);

        // If we have standing points to seed for sorting
        if ($standingPoints !== null && $previousRound !== null) {
            Standing::create([
                'round_id' => $previousRound->id,
                'user_id' => $player->id,
                'position' => 1,
                'points' => $standingPoints,
            ]);
        }

        return $player;
    }

    public function test_pairs_players_in_score_brackets(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        // Create 4 players with distinct ELO ratings (determines fixed pairing order)
        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1800]);
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1600]);
        $playerD = $this->createPlayerUser('Dave', 'dave@example.com');
        $playerD->update(['elo_rating' => 1400]);

        // R1 results: Alice beat Carol (1-0), Bob beat Dave (1-0)
        // Game scores after R1: Alice=1, Bob=1, Carol=0, Dave=0
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerB->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        // Register all for round 2
        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);
        $this->assertCount(2, $gamePairings);

        // Bracket 1.0 (Alice + Bob, both won): top-half Alice(ELO 2000) vs bottom-half Bob(ELO 1800)
        $board1 = $gamePairings->firstWhere('board_number', 1);
        $board1Ids = [$board1->white_user_id, $board1->black_user_id];
        $this->assertContains($playerA->id, $board1Ids, 'Alice should be in bracket 1.0');
        $this->assertContains($playerB->id, $board1Ids, 'Bob should be in bracket 1.0');

        // Bracket 0.0 (Carol + Dave, both lost): top-half Carol(ELO 1600) vs bottom-half Dave(ELO 1400)
        $board2 = $gamePairings->firstWhere('board_number', 2);
        $board2Ids = [$board2->white_user_id, $board2->black_user_id];
        $this->assertContains($playerC->id, $board2Ids, 'Carol should be in bracket 0.0');
        $this->assertContains($playerD->id, $board2Ids, 'Dave should be in bracket 0.0');
    }

    public function test_no_repeat_pairing_in_period(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        // Create a second round in the same period
        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerD = $this->createPlayerUser('Dave', 'dave@example.com');

        // Alice vs Bob in round 1 (already played)
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye' => false,
            'result' => '1-0',
        ]);

        // Carol vs Dave in round 1
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 2,
            'white_user_id' => $playerC->id,
            'black_user_id' => $playerD->id,
            'is_bye' => false,
            'result' => '1-0',
        ]);

        // Register all four for round 2
        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create([
                'round_id' => $round2->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);

        // Alice and Bob should NOT be paired together again
        $aliceBobRepeat = $gamePairings->filter(function ($p) use ($playerA, $playerB) {
            return ($p->white_user_id === $playerA->id && $p->black_user_id === $playerB->id)
                || ($p->white_user_id === $playerB->id && $p->black_user_id === $playerA->id);
        });

        $this->assertCount(0, $aliceBobRepeat);
    }

    public function test_repeat_allowed_if_default_loss(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');

        // Alice vs Bob in round 1, but Bob was absent (default loss)
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye' => false,
            'result' => '1-0',
        ]);

        RoundPlayerStatus::create([
            'round_id' => $round1->id,
            'user_id' => $playerB->id,
            'status' => 'absent',
        ]);

        // Register both for round 2
        Registration::create(['round_id' => $round2->id, 'user_id' => $playerA->id, 'status' => 'available']);
        Registration::create(['round_id' => $round2->id, 'user_id' => $playerB->id, 'status' => 'available']);

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);

        // With only 2 players, they must be paired together (repeat allowed since Bob was absent)
        $this->assertCount(1, $gamePairings);
        $pairing = $gamePairings->first();

        $this->assertTrue(
            ($pairing->white_user_id === $playerA->id && $pairing->black_user_id === $playerB->id)
            || ($pairing->white_user_id === $playerB->id && $pairing->black_user_id === $playerA->id)
        );
    }

    public function test_odd_player_gets_bye(): void
    {
        [$season, $period, $round] = $this->createSeasonWithRound();

        // Create 3 players (odd)
        $players = [];
        for ($i = 1; $i <= 3; $i++) {
            $player = $this->createPlayerUser("Player $i", "player{$i}@example.com");
            Registration::create([
                'round_id' => $round->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
            $players[] = $player;
        }

        $pairings = $this->engine->generatePairings($round);

        $byePairings = $pairings->where('is_bye', true);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(1, $byePairings);
        $this->assertCount(1, $gamePairings);

        // The bye player should have a valid user_id
        $this->assertNotNull($byePairings->first()->bye_user_id);
    }

    public function test_color_alternation(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');

        // In round 1: Alice played white, Bob played black
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye' => false,
            'result' => 'remise',
        ]);

        // Register both for round 2
        Registration::create(['round_id' => $round2->id, 'user_id' => $playerA->id, 'status' => 'available']);
        Registration::create(['round_id' => $round2->id, 'user_id' => $playerB->id, 'status' => 'available']);

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(1, $gamePairings);
        $pairing = $gamePairings->first();

        // Alice played white last time, so she should play black this time
        // Bob played black last time, so he should play white this time
        $this->assertEquals($playerB->id, $pairing->white_user_id);
        $this->assertEquals($playerA->id, $pairing->black_user_id);
    }

    public function test_round1_sorts_by_elo_as_tiebreaker(): void
    {
        [$season, $period, $round] = $this->createSeasonWithRound();

        // All players have 0 points (round 1), but different ELO ratings
        $strongPlayer = $this->createAndRegisterPlayer($round, 2000);
        $mediumPlayer = $this->createAndRegisterPlayer($round, 1500);
        $weakPlayer1 = $this->createAndRegisterPlayer($round, 1000);
        $weakPlayer2 = $this->createAndRegisterPlayer($round, 900);

        $pairings = $this->engine->generatePairings($round);

        $gamePairings = $pairings->where('is_bye', false);
        $this->assertCount(2, $gamePairings);

        // With 0 points and ELO tiebreaker, order should be: 2000, 1500, 1000, 900
        // Swiss top-vs-bottom: board 1 = strongest vs 3rd, board 2 = 2nd vs weakest
        $board1 = $gamePairings->firstWhere('board_number', 1);
        $board2 = $gamePairings->firstWhere('board_number', 2);

        // Board 1 should have the strongest player
        $board1Players = [$board1->white_user_id, $board1->black_user_id];
        $this->assertTrue(in_array($strongPlayer->id, $board1Players));

        // Board 2 should have the second strongest player
        $board2Players = [$board2->white_user_id, $board2->black_user_id];
        $this->assertTrue(in_array($mediumPlayer->id, $board2Players));
    }

    public function test_brackets_use_game_scores_not_keizer_points(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1800]);
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1600]);
        $playerD = $this->createPlayerUser('Dave', 'dave@example.com');
        $playerD->update(['elo_rating' => 1400]);

        // R1 results: Alice beat Carol (1-0), Bob beat Dave (1-0)
        // Game scores: Alice=1, Bob=1, Carol=0, Dave=0
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerB->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        // Seed different Keizer standings (Alice=100, Bob=80 even though both won)
        // The point is: brackets should group by game score (both have 1.0), NOT Keizer points
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 40]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 30]);

        // Register all for round 2
        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(2, $gamePairings);

        // Game scores: Alice=1, Bob=1 (bracket 1.0) and Carol=0, Dave=0 (bracket 0.0)
        // Within bracket 1.0: Alice(2000 ELO) top, Bob(1800) bottom → Alice vs Bob
        // Within bracket 0.0: Carol(1600 ELO) top, Dave(1400) bottom → Carol vs Dave
        $board1 = $gamePairings->firstWhere('board_number', 1);
        $board1Ids = [$board1->white_user_id, $board1->black_user_id];
        $this->assertContains($playerA->id, $board1Ids, 'Alice should be in bracket 1.0 pairing');
        $this->assertContains($playerB->id, $board1Ids, 'Bob should be in bracket 1.0 pairing');

        $board2 = $gamePairings->firstWhere('board_number', 2);
        $board2Ids = [$board2->white_user_id, $board2->black_user_id];
        $this->assertContains($playerC->id, $board2Ids, 'Carol should be in bracket 0.0 pairing');
        $this->assertContains($playerD->id, $board2Ids, 'Dave should be in bracket 0.0 pairing');
    }

    public function test_color_conflict_avoidance_in_swiss(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'completed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $round3 = Round::create([
            'period_id' => $period->id,
            'round_number' => 3,
            'season_round_number' => 3,
            'date' => '2025-01-21',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-20 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1800]);
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1600]);
        $playerD = $this->createPlayerUser('Dave', 'dave@example.com');
        $playerD->update(['elo_rating' => 1400]);

        // R1: Alice(W) vs Carol(B), Bob(W) vs Dave(B)
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerB->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        // R2: Alice(W) vs Bob(B), Carol(W) vs Dave(B)
        // After R2: Alice has 2W/0B, Bob has 1W/1B
        Pairing::create(['round_id' => $round2->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round2->id, 'board_number' => 2, 'white_user_id' => $playerC->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        // Standings after R2
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 120]);
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 40]);

        // Register all for R3
        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create(['round_id' => $round3->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round3);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(2, $gamePairings);

        // After R2: Alice has 2W/0B (must play black, ±2 limit)
        // Carol has 2W/0B (must play black, ±2 limit)
        // If Swiss tried to pair Alice vs Carol (same bracket with game score 2.0),
        // both MUST play black → color conflict → engine should swap to avoid this
        // The test verifies that no pairing has both players needing the same forced color
        foreach ($gamePairings as $pairing) {
            // Simply verify that valid pairings were created (engine didn't crash)
            $this->assertNotNull($pairing->white_user_id);
            $this->assertNotNull($pairing->black_user_id);
            $this->assertNotEquals($pairing->white_user_id, $pairing->black_user_id);
        }

        // Verify Alice plays black (she has 2 consecutive whites and balance +2)
        $alicePairing = $gamePairings->first(function ($p) use ($playerA) {
            return $p->white_user_id === $playerA->id || $p->black_user_id === $playerA->id;
        });
        $this->assertEquals($playerA->id, $alicePairing->black_user_id, 'Alice (2W/0B) must play black');

        // Verify Carol plays black (she also has 2 consecutive whites and balance +2)
        $carolPairing = $gamePairings->first(function ($p) use ($playerC) {
            return $p->white_user_id === $playerC->id || $p->black_user_id === $playerC->id;
        });
        $this->assertEquals($playerC->id, $carolPairing->black_user_id, 'Carol (2W/0B) must play black');
    }

    public function test_repeat_blocked_pair_floats_to_next_bracket(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        // 4 players with distinct ELOs
        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1800]);
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1600]);
        $playerD = $this->createPlayerUser('Dave', 'dave@example.com');
        $playerD->update(['elo_rating' => 1400]);

        // Round 1: Alice beat Bob (both now in bracket 1.0), Carol beat Dave (both in bracket 0.0 as losers... wait)
        // Actually: Alice=1, Bob=0 after round 1 if Alice beat Bob. We need both A and B in bracket 1.0.
        // Let's have Alice and Bob both win in round 1 (different opponents), and already play each other via a prior game.
        // Simpler: Alice(W) beat Carol, Bob(W) beat Dave → Alice=1, Bob=1 → bracket 1.0; Carol=0, Dave=0 → bracket 0.0
        // Then also seed a period pairing: Alice already played Bob this period (earlier round 0).
        $round0 = Round::create([
            'period_id' => $period->id,
            'round_number' => 0,
            'season_round_number' => 0,
            'date' => '2024-12-31',
            'status' => 'completed',
            'registration_deadline' => '2024-12-30 18:00:00',
        ]);

        // Round 0: Alice already played Bob this period (blocked for round 2)
        Pairing::create(['round_id' => $round0->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round0->id, 'board_number' => 2, 'white_user_id' => $playerC->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        // Round 1: Alice beat Carol, Bob beat Dave → Alice=1, Bob=1 (bracket 1.0), Carol=0, Dave=0 (bracket 0.0)
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerB->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);

        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false)->values();

        // Must produce 2 game pairings
        $this->assertCount(2, $gamePairings, 'Should produce exactly 2 pairings for 4 players');

        // All 4 players must appear exactly once
        $allIds = $gamePairings->flatMap(fn ($p) => [$p->white_user_id, $p->black_user_id])->values();
        $this->assertCount(4, $allIds->unique(), 'All 4 players should appear exactly once');

        // Alice and Bob must NOT be paired (already played this period)
        $aliceBobRepeat = $gamePairings->filter(fn ($p) => ($p->white_user_id === $playerA->id && $p->black_user_id === $playerB->id)
            || ($p->white_user_id === $playerB->id && $p->black_user_id === $playerA->id)
        );
        $this->assertCount(0, $aliceBobRepeat, 'Alice and Bob should not be paired again (floated to next bracket)');

        // Carol and Dave must NOT be paired (already played this period)
        $carolDaveRepeat = $gamePairings->filter(fn ($p) => ($p->white_user_id === $playerC->id && $p->black_user_id === $playerD->id)
            || ($p->white_user_id === $playerD->id && $p->black_user_id === $playerC->id)
        );
        $this->assertCount(0, $carolDaveRepeat, 'Carol and Dave should not be paired again');
    }

    public function test_swiss_bye_excludes_forfeit_winners(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound('completed');

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayerUser('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);
        $playerB = $this->createPlayerUser('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1500]);
        $playerC = $this->createPlayerUser('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1000]);

        // R1: Alice vs Bob, but Bob was absent (forfeit win for Alice)
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        RoundPlayerStatus::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'status' => 'absent']);

        // Carol got a real bye in R1
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerC->id]);

        // Register 3 players for R2 (odd → one gets bye)
        foreach ([$playerA, $playerB, $playerC] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $byePairings = $pairings->where('is_bye', true);

        $this->assertCount(1, $byePairings);

        // Alice has forfeit win (counts as bye), Carol has actual bye
        // Bob has no bye history → Bob should get the bye
        $this->assertEquals($playerB->id, $byePairings->first()->bye_user_id, 'Bob (no bye/forfeit history) should get the bye');
    }
}

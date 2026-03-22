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
use App\Services\KeizerPairingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeizerPairingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected KeizerPairingEngine $engine;

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

        $this->engine = new KeizerPairingEngine;
    }

    protected function createSeasonWithRound(): array
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
            'pairing_system' => 'keizer',
        ]);

        $round = Round::create([
            'period_id' => $period->id,
            'round_number' => 1,
            'season_round_number' => 1,
            'date' => '2025-01-07',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-06 18:00:00',
        ]);

        return [$season, $period, $round];
    }

    protected function createPlayer(string $name, string $email): User
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

    public function test_pairs_by_ranking_order(): void
    {
        [$season, $period, $round] = $this->createSeasonWithRound();

        // Create a previous completed round with standings
        $prevRound = Round::create([
            'period_id' => $period->id,
            'round_number' => 0,
            'season_round_number' => 0,
            'date' => '2024-12-31',
            'status' => 'completed',
            'registration_deadline' => '2024-12-30 18:00:00',
        ]);

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');
        $playerD = $this->createPlayer('Dave', 'dave@example.com');

        // Seed standings: A=100, B=80, C=60, D=40
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 40]);

        // Register all four
        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create([
                'round_id' => $round->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
        }

        $pairings = $this->engine->generatePairings($round);
        $gamePairings = $pairings->where('is_bye', false)->values();

        // Expect 2 games: #1 vs #2, #3 vs #4
        $this->assertCount(2, $gamePairings);

        // Pairing 1: Alice (#1) vs Bob (#2)
        $firstPairing = $gamePairings->first();
        $firstPairingIds = [$firstPairing->white_user_id, $firstPairing->black_user_id];
        $this->assertContains($playerA->id, $firstPairingIds);
        $this->assertContains($playerB->id, $firstPairingIds);

        // Pairing 2: Carol (#3) vs Dave (#4)
        $secondPairing = $gamePairings->last();
        $secondPairingIds = [$secondPairing->white_user_id, $secondPairing->black_user_id];
        $this->assertContains($playerC->id, $secondPairingIds);
        $this->assertContains($playerD->id, $secondPairingIds);
    }

    public function test_skips_repeat_opponents(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        // Mark round1 as completed
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');

        // Seed standings from round1: A=100, B=80, C=60
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);

        // In round 1: Alice played Bob (already paired)
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye' => false,
            'result' => '1-0',
        ]);
        Pairing::create([
            'round_id' => $round1->id,
            'board_number' => 2,
            'white_user_id' => null,
            'black_user_id' => null,
            'is_bye' => true,
            'bye_user_id' => $playerC->id,
        ]);

        // Register all 3 for round 2 (odd - one gets bye)
        foreach ([$playerA, $playerB, $playerC] as $player) {
            Registration::create([
                'round_id' => $round2->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false);

        // Alice and Bob should NOT be paired together (already played in period)
        $aliceBobRepeat = $gamePairings->filter(function ($p) use ($playerA, $playerB) {
            return ($p->white_user_id === $playerA->id && $p->black_user_id === $playerB->id)
                || ($p->white_user_id === $playerB->id && $p->black_user_id === $playerA->id);
        });

        $this->assertCount(0, $aliceBobRepeat);
    }

    public function test_odd_player_gets_bye(): void
    {
        [$season, $period, $round] = $this->createSeasonWithRound();

        // Create 3 players (odd number)
        for ($i = 1; $i <= 3; $i++) {
            $player = $this->createPlayer("Player $i", "player{$i}_keizer@example.com");
            Registration::create([
                'round_id' => $round->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
        }

        $pairings = $this->engine->generatePairings($round);

        $byePairings = $pairings->where('is_bye', true);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(1, $byePairings);
        $this->assertCount(1, $gamePairings);
        $this->assertNotNull($byePairings->first()->bye_user_id);
    }

    public function test_color_balance_considered(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');

        // In round 1: Alice was white, Bob was black
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

        // Alice played white in R1, should play black in R2 (color alternation)
        // Bob played black in R1, should play white in R2
        $this->assertEquals($playerB->id, $pairing->white_user_id);
        $this->assertEquals($playerA->id, $pairing->black_user_id);
    }

    public function test_elo_tiebreaker_in_ranking(): void
    {
        [$season, $period, $round] = $this->createSeasonWithRound();

        // Create a previous completed round with standings
        $prevRound = Round::create([
            'period_id' => $period->id,
            'round_number' => 0,
            'season_round_number' => 0,
            'date' => '2024-12-31',
            'status' => 'completed',
            'registration_deadline' => '2024-12-30 18:00:00',
        ]);

        // All four players have EQUAL Keizer points but different ELO
        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerA->update(['elo_rating' => 2000]);

        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerB->update(['elo_rating' => 1800]);

        $playerC = $this->createPlayer('Carol', 'carol@example.com');
        $playerC->update(['elo_rating' => 1500]);

        $playerD = $this->createPlayer('Dave', 'dave@example.com');
        $playerD->update(['elo_rating' => 1200]);

        // All have 80 points - equal Keizer standings
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 80]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 80]);
        Standing::create(['round_id' => $prevRound->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 80]);

        foreach ([$playerA, $playerB, $playerC, $playerD] as $player) {
            Registration::create([
                'round_id' => $round->id,
                'user_id' => $player->id,
                'status' => 'available',
            ]);
        }

        $pairings = $this->engine->generatePairings($round);
        $gamePairings = $pairings->where('is_bye', false)->values();

        $this->assertCount(2, $gamePairings);

        // With ELO tiebreaker, ranking should be: Alice(2000) > Bob(1800) > Carol(1500) > Dave(1200)
        // Keizer pairs #1 vs #2: Alice vs Bob
        $firstPairing = $gamePairings->first();
        $firstPairingIds = [$firstPairing->white_user_id, $firstPairing->black_user_id];
        $this->assertContains($playerA->id, $firstPairingIds, 'Alice (highest ELO) should be in first pairing');
        $this->assertContains($playerB->id, $firstPairingIds, 'Bob (2nd highest ELO) should be in first pairing');

        // #3 vs #4: Carol vs Dave
        $secondPairing = $gamePairings->last();
        $secondPairingIds = [$secondPairing->white_user_id, $secondPairing->black_user_id];
        $this->assertContains($playerC->id, $secondPairingIds, 'Carol (3rd ELO) should be in second pairing');
        $this->assertContains($playerD->id, $secondPairingIds, 'Dave (lowest ELO) should be in second pairing');
    }

    public function test_safe_force_pairing_gives_bye_not_double_bye(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        // Create 5 players (odd) where re-pairing constraints force some into the fallback
        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');
        $playerD = $this->createPlayer('Dave', 'dave@example.com');
        $playerE = $this->createPlayer('Eve', 'eve@example.com');

        // Seed standings so ranking is A > B > C > D > E
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 40]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerE->id, 'position' => 5, 'points' => 20]);

        // Round 1 pairings: A-B, C-D, E=bye
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerC->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 3, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerE->id]);

        // Register all 5 for round 2
        foreach ([$playerA, $playerB, $playerC, $playerD, $playerE] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);

        $gamePairings = $pairings->where('is_bye', false);
        $byePairings = $pairings->where('is_bye', true);

        // Must have exactly 2 games and 1 bye (5 players total)
        $this->assertCount(2, $gamePairings, 'Should have exactly 2 game pairings with 5 players');
        $this->assertCount(1, $byePairings, 'Should have exactly 1 bye with 5 players');

        // All 5 players should appear exactly once
        $allPlayerIds = $gamePairings->flatMap(fn ($p) => [$p->white_user_id, $p->black_user_id])
            ->merge($byePairings->pluck('bye_user_id'))
            ->filter()
            ->values();

        $this->assertCount(5, $allPlayerIds, 'All 5 players should be accounted for');

        // Eve had a bye in round 1, so she should NOT get the bye again if avoidable
        $byePlayerId = $byePairings->first()->bye_user_id;
        $this->assertNotEquals($playerE->id, $byePlayerId, 'Eve already had a bye in round 1 and should not get another');
    }

    public function test_bye_prefers_player_without_prior_bye(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');

        // Seed standings: A=100, B=80, C=60 (Carol is lowest-ranked)
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);

        // In round 1: Carol already had a bye
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerC->id]);

        foreach ([$playerA, $playerB, $playerC] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $byePairings = $pairings->where('is_bye', true);

        $this->assertCount(1, $byePairings);
        // Carol had a bye in R1, so someone else (Bob, lowest ranked without prior bye) should get the bye
        $this->assertNotEquals($playerC->id, $byePairings->first()->bye_user_id, 'Carol already had a bye and should not receive another');
    }

    public function test_percentage_based_color_allocation(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
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

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');

        // R1: Alice(W) vs Bob(B), Carol bye
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerC->id]);

        // R2: Alice(W) vs Carol(B)  — Alice now has 2 whites, 0 blacks (100% white)
        $round2->update(['status' => 'completed']);
        Pairing::create(['round_id' => $round2->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round2->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerB->id]);

        // R3: Alice vs Bob again. Alice has 100% white (2W/0B), Bob has 0% white (0W/1B).
        // The ±2 hard constraint forces Alice to play black.
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 50]);

        Registration::create(['round_id' => $round3->id, 'user_id' => $playerA->id, 'status' => 'available']);
        Registration::create(['round_id' => $round3->id, 'user_id' => $playerB->id, 'status' => 'available']);

        $pairings = $this->engine->generatePairings($round3);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(1, $gamePairings);
        $pairing = $gamePairings->first();

        // Alice must play black (balance +2), Bob must play white (balance -1 + percentage)
        $this->assertEquals($playerB->id, $pairing->white_user_id, 'Bob (0% white) should play white');
        $this->assertEquals($playerA->id, $pairing->black_user_id, 'Alice (100% white, balance +2) must play black');
    }

    public function test_no_three_consecutive_same_color(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

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

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');

        // R1: Alice(W) vs Carol(B) — Alice white #1
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerC->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerB->id]);

        // R2: Alice(W) vs Bob(B) — Alice white #2 consecutive
        Pairing::create(['round_id' => $round2->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round2->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerC->id]);

        // R3: Alice must NOT play white (would be 3 in a row)
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round2->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 50]);

        Registration::create(['round_id' => $round3->id, 'user_id' => $playerA->id, 'status' => 'available']);
        Registration::create(['round_id' => $round3->id, 'user_id' => $playerB->id, 'status' => 'available']);

        $pairings = $this->engine->generatePairings($round3);
        $gamePairings = $pairings->where('is_bye', false);

        $this->assertCount(1, $gamePairings);
        $pairing = $gamePairings->first();

        // Alice had 2 consecutive whites, MUST play black
        $this->assertEquals($playerA->id, $pairing->black_user_id, 'Alice must play black after 2 consecutive whites');
        $this->assertEquals($playerB->id, $pairing->white_user_id, 'Bob should play white');
    }

    public function test_backtracking_resolves_repeat_pairing(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        // 6 players ranked A > B > C > D > E > F
        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');
        $playerD = $this->createPlayer('Dave', 'dave@example.com');
        $playerE = $this->createPlayer('Eve', 'eve@example.com');
        $playerF = $this->createPlayer('Frank', 'frank@example.com');

        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 120]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerD->id, 'position' => 4, 'points' => 60]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerE->id, 'position' => 5, 'points' => 40]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerF->id, 'position' => 6, 'points' => 20]);

        // Round 1 pairings: A–B, C–D, E–F (all pairs blocked for round 2)
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => $playerC->id, 'black_user_id' => $playerD->id, 'is_bye' => false, 'result' => '1-0']);
        Pairing::create(['round_id' => $round1->id, 'board_number' => 3, 'white_user_id' => $playerE->id, 'black_user_id' => $playerF->id, 'is_bye' => false, 'result' => '1-0']);

        foreach ([$playerA, $playerB, $playerC, $playerD, $playerE, $playerF] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $gamePairings = $pairings->where('is_bye', false)->values();

        // Must produce exactly 3 game pairings (6 players, no byes)
        $this->assertCount(3, $gamePairings, 'Should produce exactly 3 pairings for 6 players');

        // All 6 players must appear exactly once
        $allIds = $gamePairings->flatMap(fn ($p) => [$p->white_user_id, $p->black_user_id])->values();
        $this->assertCount(6, $allIds->unique(), 'All 6 players should appear exactly once');

        // No repeat pairings: none of the round-1 pairs should appear again
        $round1Pairs = [
            [$playerA->id, $playerB->id],
            [$playerC->id, $playerD->id],
            [$playerE->id, $playerF->id],
        ];

        foreach ($round1Pairs as [$id1, $id2]) {
            $repeat = $gamePairings->filter(fn ($p) => ($p->white_user_id === $id1 && $p->black_user_id === $id2)
                || ($p->white_user_id === $id2 && $p->black_user_id === $id1)
            );
            $this->assertCount(0, $repeat, "Players $id1 and $id2 should not be paired again (backtracking should have resolved this)");
        }
    }

    public function test_forfeit_win_counts_as_bye_in_history(): void
    {
        [$season, $period, $round1] = $this->createSeasonWithRound();
        $round1->update(['status' => 'completed']);

        $round2 = Round::create([
            'period_id' => $period->id,
            'round_number' => 2,
            'season_round_number' => 2,
            'date' => '2025-01-14',
            'status' => 'registration_closed',
            'registration_deadline' => '2025-01-13 18:00:00',
        ]);

        $playerA = $this->createPlayer('Alice', 'alice@example.com');
        $playerB = $this->createPlayer('Bob', 'bob@example.com');
        $playerC = $this->createPlayer('Carol', 'carol@example.com');

        // Seed standings
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerA->id, 'position' => 1, 'points' => 100]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'position' => 2, 'points' => 80]);
        Standing::create(['round_id' => $round1->id, 'user_id' => $playerC->id, 'position' => 3, 'points' => 60]);

        // R1: Alice vs Bob, but Bob was absent (forfeit win for Alice)
        Pairing::create(['round_id' => $round1->id, 'board_number' => 1, 'white_user_id' => $playerA->id, 'black_user_id' => $playerB->id, 'is_bye' => false, 'result' => '1-0']);
        RoundPlayerStatus::create(['round_id' => $round1->id, 'user_id' => $playerB->id, 'status' => 'absent']);

        // Carol also got a bye in R1
        Pairing::create(['round_id' => $round1->id, 'board_number' => 2, 'white_user_id' => null, 'black_user_id' => null, 'is_bye' => true, 'bye_user_id' => $playerC->id]);

        // In round 2 with 3 players (odd): Alice's forfeit win should count like a bye
        // Neither Alice nor Carol should get the bye again preferentially
        foreach ([$playerA, $playerB, $playerC] as $player) {
            Registration::create(['round_id' => $round2->id, 'user_id' => $player->id, 'status' => 'available']);
        }

        $pairings = $this->engine->generatePairings($round2);
        $byePairings = $pairings->where('is_bye', true);

        $this->assertCount(1, $byePairings);

        // Both Alice (forfeit win) and Carol (actual bye) have "bye-like" history
        // Bob has no bye history (he was absent, not a bye recipient)
        // So Bob should NOT get the bye — the engine picks from lowest rank without bye history
        // Bob is lowest ranked without bye history... wait, Bob was the absent player, not a bye recipient
        // So the bye should go to Bob since he has no bye history
        $byePlayerId = $byePairings->first()->bye_user_id;
        $this->assertEquals($playerB->id, $byePlayerId, 'Bob (no bye/forfeit history) should get the bye');
    }
}

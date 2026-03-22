<?php

namespace Tests\Feature;

use App\Models\Pairing;
use App\Models\Period;
use App\Models\Round;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Standing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Keizer settings required by KeizerPointsService
        Setting::set('max_waardering', 60);
        Setting::set('punten_extern', 40);
        Setting::set('punten_afwezig', 20);
        Setting::set('punten_oneven', 40);
        Setting::set('max_afwezig', 5);
        Setting::set('punten_nieuwe_speler', 15);
        Setting::set('factor_winst', 1);
        Setting::set('factor_remise', 0.5);
        Setting::set('factor_verlies', 0);
    }

    protected function createWedstrijdleider(): User
    {
        $user = new User([
            'name'             => 'Admin',
            'email'            => 'admin@example.com',
            'is_active'        => true,
            'auto_participate' => false,
        ]);
        $user->role = 'wedstrijdleider';
        $user->save();

        return $user;
    }

    protected function createPlayer(string $name, string $email, int $initialRanking = null): User
    {
        $user = new User([
            'name'             => $name,
            'email'            => $email,
            'is_active'        => true,
            'auto_participate' => false,
            'elo_rating'       => $initialRanking,
        ]);
        $user->role = 'speler';
        $user->save();

        return $user;
    }

    protected function createSeasonWithRound(string $status = 'paired'): array
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

        $round = Round::create([
            'period_id'             => $period->id,
            'round_number'          => 1,
            'season_round_number'   => 1,
            'date'                  => '2025-01-07',
            'status'                => $status,
            'registration_deadline' => '2025-01-06 18:00:00',
        ]);

        return [$season, $period, $round];
    }

    public function test_wedstrijdleider_can_enter_results(): void
    {
        $admin = $this->createWedstrijdleider();
        [$season, $period, $round] = $this->createSeasonWithRound('paired');

        $playerA = $this->createPlayer('Alice', 'alice@example.com', 1);
        $playerB = $this->createPlayer('Bob', 'bob@example.com', 2);

        $pairing = Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye'        => false,
        ]);

        $response = $this->actingAs($admin)->post("/beheer/rondes/{$round->id}/resultaten", [
            'results' => [
                $pairing->id => ['result' => '1-0'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pairings', [
            'id'     => $pairing->id,
            'result' => '1-0',
        ]);
    }

    public function test_completing_round_recalculates_standings(): void
    {
        $admin = $this->createWedstrijdleider();
        [$season, $period, $round] = $this->createSeasonWithRound('paired');

        $playerA = $this->createPlayer('Alice', 'alice@example.com', 1800);
        $playerB = $this->createPlayer('Bob', 'bob@example.com', 1600);

        // Create a pairing with a result
        Pairing::create([
            'round_id'      => $round->id,
            'board_number'  => 1,
            'white_user_id' => $playerA->id,
            'black_user_id' => $playerB->id,
            'is_bye'        => false,
            'result'        => '1-0',
        ]);

        // Confirm no standings yet
        $this->assertDatabaseCount('standings', 0);

        // Complete the round
        $response = $this->actingAs($admin)->post("/beheer/rondes/{$round->id}/afronden");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Round status should be updated to 'completed'
        $this->assertDatabaseHas('rounds', [
            'id'     => $round->id,
            'status' => 'completed',
        ]);

        // Standings should be created for both players
        $this->assertDatabaseHas('standings', [
            'round_id' => $round->id,
            'user_id'  => $playerA->id,
        ]);
        $this->assertDatabaseHas('standings', [
            'round_id' => $round->id,
            'user_id'  => $playerB->id,
        ]);

        // Alice (ELO 1800, rank 1) won against Bob (ELO 1600, rank 2 = value 59), Bob lost (0 pts)
        $aliceStanding = Standing::where('round_id', $round->id)
            ->where('user_id', $playerA->id)
            ->first();
        $this->assertNotNull($aliceStanding);
        $this->assertEquals(59.0, (float) $aliceStanding->points);
        $this->assertEquals(1, $aliceStanding->position);

        $bobStanding = Standing::where('round_id', $round->id)
            ->where('user_id', $playerB->id)
            ->first();
        $this->assertNotNull($bobStanding);
        $this->assertEquals(0.0, (float) $bobStanding->points);
        $this->assertEquals(2, $bobStanding->position);
    }

    public function test_non_admin_cannot_access_results_page(): void
    {
        $player = $this->createPlayer('Alice', 'alice@example.com');
        [$season, $period, $round] = $this->createSeasonWithRound();

        $response = $this->actingAs($player)->get("/beheer/rondes/{$round->id}/resultaten");

        // Should be forbidden or redirected
        $response->assertStatus(403);
    }
}

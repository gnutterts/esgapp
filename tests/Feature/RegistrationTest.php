<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\Registration;
use App\Models\Round;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role = 'speler', array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'auto_participate' => false,
        ], $attributes));
        $user->role = $role;
        $user->save();

        return $user;
    }

    protected function createRound(array $attributes = [], ?Season $season = null, ?Period $period = null): Round
    {
        if (! $season) {
            $season = Season::where('is_current', true)->first() ?? Season::create([
                'name' => 'Test Season',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'is_current' => true,
            ]);
        }

        if (! $period) {
            $period = $season->periods()->first() ?? Period::create([
                'season_id' => $season->id,
                'number' => 1,
                'pairing_system' => 'keizer',
            ]);
        }

        $seasonRoundNumber = Round::whereHas('period', function ($q) use ($season) {
            $q->where('season_id', $season->id);
        })->count() + 1;

        return Round::create(array_merge([
            'period_id' => $period->id,
            'round_number' => $period->rounds()->count() + 1,
            'season_round_number' => $seasonRoundNumber,
            'date' => '2025-01-07',
            'status' => 'scheduled',
            'registration_deadline' => now()->addHours(2),
        ], $attributes));
    }

    // -------------------------------------------------------
    // Existing tests (non-auto-participate user behavior)
    // -------------------------------------------------------

    public function test_can_toggle_registration(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();

        // First toggle: should create an 'available' registration
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'available',
        ]);

        // Second toggle: should switch to 'unavailable'
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);

        // Third toggle: should switch back to 'available'
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'available',
        ]);
    }

    public function test_cannot_register_after_deadline(): void
    {
        $user = $this->createUser();
        $round = $this->createRound([
            'registration_deadline' => now()->subHour(), // deadline passed
        ]);

        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // No registration should be created
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_cannot_register_when_round_is_not_scheduled(): void
    {
        $user = $this->createUser();
        $round = $this->createRound([
            'status' => 'paired',
        ]);

        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_can_toggle_auto_participate(): void
    {
        $user = $this->createUser();
        $this->assertFalse($user->auto_participate);

        // Enable auto_participate
        $response = $this->actingAs($user)->post('/auto-deelname');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->auto_participate);

        // Disable auto_participate
        $response = $this->actingAs($user)->post('/auto-deelname');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->auto_participate);
    }

    // -------------------------------------------------------
    // Auto-participate toggle behavior
    // -------------------------------------------------------

    public function test_auto_participate_user_first_toggle_creates_unavailable(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // Auto-participate user has no registration (virtually available).
        // First click should create 'unavailable' (explicit opt-out).
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);
    }

    public function test_auto_participate_user_toggle_from_unavailable_deletes_registration(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // Create an explicit opt-out registration
        Registration::create([
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);

        // Toggling back should DELETE the registration (return to virtual available)
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_auto_participate_user_toggle_from_available_sets_unavailable(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // Edge case: user has an 'available' registration (e.g. materialized at close)
        Registration::create([
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'available',
        ]);

        // Toggling should flip to 'unavailable' (standard behavior)
        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);
    }

    public function test_auto_participate_full_toggle_cycle(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // Start: no registration (virtually available)
        $this->assertDatabaseCount('registrations', 0);

        // Toggle 1: opt-out → creates 'unavailable'
        $this->actingAs($user)->post("/registratie/{$round->id}");
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);

        // Toggle 2: opt-back-in → deletes registration (return to virtual available)
        $this->actingAs($user)->post("/registratie/{$round->id}");
        $this->assertDatabaseMissing('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
        ]);

        // Toggle 3: opt-out again → creates 'unavailable'
        $this->actingAs($user)->post("/registratie/{$round->id}");
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);
    }

    // -------------------------------------------------------
    // Disabling auto-participate cleans up virtual availability
    // -------------------------------------------------------

    public function test_disabling_auto_participate_makes_user_unavailable_for_open_rounds(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // No registration exists — user is virtually available
        $this->assertDatabaseCount('registrations', 0);

        // Disable auto-participate
        $response = $this->actingAs($user)->post('/auto-deelname');
        $user->refresh();
        $this->assertFalse($user->auto_participate);

        // Still no registration — user is now truly unavailable (not virtually available)
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_disabling_auto_participate_preserves_explicit_opt_out(): void
    {
        $user = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // User explicitly opts out of this round
        Registration::create([
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);

        // Disable auto-participate
        $this->actingAs($user)->post('/auto-deelname');
        $user->refresh();
        $this->assertFalse($user->auto_participate);

        // Opt-out registration still exists (not cleaned up)
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'unavailable',
        ]);
    }

    // -------------------------------------------------------
    // Close registration materializes auto-participate users
    // -------------------------------------------------------

    public function test_close_registration_materializes_auto_participate_users(): void
    {
        $admin = $this->createUser('wedstrijdleider');
        $autoUser1 = $this->createUser('speler', ['auto_participate' => true]);
        $autoUser2 = $this->createUser('speler', ['auto_participate' => true]);
        $manualUser = $this->createUser('speler', ['auto_participate' => false]);
        $round = $this->createRound();

        // Manual user registers themselves
        Registration::create([
            'round_id' => $round->id,
            'user_id' => $manualUser->id,
            'status' => 'available',
        ]);

        // No registrations for auto-participate users (they are virtually available)
        $this->assertDatabaseMissing('registrations', ['user_id' => $autoUser1->id]);
        $this->assertDatabaseMissing('registrations', ['user_id' => $autoUser2->id]);

        // Admin closes registration
        $response = $this->actingAs($admin)->post(route('beheer.rondes.close-registration', $round));
        $response->assertRedirect();

        // Auto-participate users should now have materialized 'available' registrations
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $autoUser1->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $autoUser2->id,
            'status' => 'available',
        ]);

        // Round status should be registration_closed
        $round->refresh();
        $this->assertEquals('registration_closed', $round->status);
    }

    public function test_close_registration_respects_explicit_opt_out(): void
    {
        $admin = $this->createUser('wedstrijdleider');
        $autoUser = $this->createUser('speler', ['auto_participate' => true]);
        $round = $this->createRound();

        // Auto-participate user explicitly opts out
        Registration::create([
            'round_id' => $round->id,
            'user_id' => $autoUser->id,
            'status' => 'unavailable',
        ]);

        // Admin closes registration
        $this->actingAs($admin)->post(route('beheer.rondes.close-registration', $round));

        // Opt-out should be preserved — NOT overwritten with 'available'
        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $autoUser->id,
            'status' => 'unavailable',
        ]);

        // Should still be only one registration for this user
        $this->assertEquals(1, Registration::where('round_id', $round->id)
            ->where('user_id', $autoUser->id)
            ->count());
    }

    public function test_close_registration_skips_inactive_auto_participate_users(): void
    {
        $admin = $this->createUser('wedstrijdleider');
        $inactiveUser = $this->createUser('speler', ['auto_participate' => true, 'is_active' => false]);
        $round = $this->createRound();

        // Admin closes registration
        $this->actingAs($admin)->post(route('beheer.rondes.close-registration', $round));

        // Inactive user should NOT get a registration
        $this->assertDatabaseMissing('registrations', [
            'round_id' => $round->id,
            'user_id' => $inactiveUser->id,
        ]);
    }

    // -------------------------------------------------------
    // Round creation no longer auto-creates registrations
    // -------------------------------------------------------

    public function test_creating_round_does_not_auto_create_registrations(): void
    {
        $admin = $this->createUser('wedstrijdleider');
        $autoUser = $this->createUser('speler', ['auto_participate' => true]);

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

        $response = $this->actingAs($admin)->post(route('beheer.rondes.store'), [
            'period_id' => $period->id,
            'date' => '2025-02-01',
            'registration_deadline' => '2025-01-31',
        ]);

        $response = $this->withoutExceptionHandling()->actingAs($admin)->post(route('beheer.rondes.store'), [
            'period_id' => $period->id,
            'date' => '2025-02-01',
            'registration_deadline' => '2025-01-31',
        ]);

        $response->assertRedirect();

        // No registrations should be created
        $this->assertDatabaseCount('registrations', 0);

        // No registrations should be created
        $this->assertDatabaseCount('registrations', 0);
    }

    // -------------------------------------------------------
    // Optional registration deadline
    // -------------------------------------------------------

    public function test_can_create_round_without_registration_deadline(): void
    {
        $admin = $this->createUser('wedstrijdleider');

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

        $response = $this->actingAs($admin)->post(route('beheer.rondes.store'), [
            'period_id' => $period->id,
            'date' => '2025-02-01',
            // no registration_deadline
        ]);

        $response->assertRedirect();

        $round = Round::first();
        $this->assertNotNull($round);
        $this->assertNull($round->registration_deadline);
        $this->assertEquals('scheduled', $round->status);
    }

    public function test_can_register_when_no_deadline_set(): void
    {
        $user = $this->createUser();
        $round = $this->createRound([
            'registration_deadline' => null,
        ]);

        $response = $this->actingAs($user)->post("/registratie/{$round->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('registrations', [
            'round_id' => $round->id,
            'user_id' => $user->id,
            'status' => 'available',
        ]);
    }
}

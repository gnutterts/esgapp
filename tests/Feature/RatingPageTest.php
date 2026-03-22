<?php

namespace Tests\Feature;

use App\Jobs\ImportKnsbRatings;
use App\Models\EloRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RatingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function createPlayer(string $name, ?int $eloRating = null, bool $showKnsbRating = true): User
    {
        $user = new User([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'elo_rating' => $eloRating,
            'show_knsb_rating' => $showKnsbRating,
        ]);
        $user->role = 'speler';
        $user->save();

        return $user;
    }

    public function test_ratings_page_shows_only_public_players(): void
    {
        $visible = $this->createPlayer('Publiek', 1800, true);
        $visible->update(['knsb_relatienummer' => '1234567']);
        $hidden = $this->createPlayer('Verborgen', 1600, false);
        $hidden->update(['knsb_relatienummer' => '7654321']);

        $response = $this->get('/ratings');

        $response->assertStatus(200);
        $response->assertSee('Publiek');
        $response->assertDontSee('Verborgen');
    }

    public function test_ratings_show_page_works_for_public_player(): void
    {
        $player = $this->createPlayer('Alice', 1800, true);

        EloRating::create([
            'user_id' => $player->id,
            'rating' => 1800,
            'source' => 'knsb',
            'measured_at' => '2026-01-01',
        ]);

        $response = $this->get("/ratings/{$player->id}");

        $response->assertStatus(200);
        $response->assertSee('Alice');
        $response->assertSee('1800');
        $response->assertSee('KNSB');
    }

    public function test_ratings_show_page_404_for_hidden_player(): void
    {
        $player = $this->createPlayer('Verborgen', 1600, false);

        $response = $this->get("/ratings/{$player->id}");

        $response->assertStatus(404);
    }

    public function test_toggle_show_knsb_rating(): void
    {
        $user = $this->createPlayer('Alice', 1800, false);

        $response = $this->actingAs($user)->post('/rating-zichtbaarheid/knsb');

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($user->fresh()->show_knsb_rating);

        // Toggle back
        $response = $this->actingAs($user)->post('/rating-zichtbaarheid/knsb');
        $this->assertFalse($user->fresh()->show_knsb_rating);
    }

    public function test_toggle_show_rating_requires_auth(): void
    {
        $response = $this->post('/rating-zichtbaarheid/knsb');

        $response->assertRedirect(route('login'));
    }

    public function test_toggle_show_rating_rejects_invalid_type(): void
    {
        $user = $this->createPlayer('Alice', 1800);

        $response = $this->actingAs($user)->post('/rating-zichtbaarheid/invalid');

        $response->assertStatus(404);
    }

    protected function createWedstrijdleider(): User
    {
        $user = new User([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $user->role = 'wedstrijdleider';
        $user->save();

        return $user;
    }

    public function test_store_with_knsb_dispatches_import_job(): void
    {
        Queue::fake();
        $admin = $this->createWedstrijdleider();

        $this->actingAs($admin)->post(route('beheer.spelers.store'), [
            'name' => 'Test Speler',
            'email' => 'test@example.com',
            'knsb_relatienummer' => '12345',
        ]);

        Queue::assertPushed(ImportKnsbRatings::class, function ($job) {
            return $job->user->knsb_relatienummer === '12345';
        });
    }

    public function test_store_without_knsb_does_not_dispatch_job(): void
    {
        Queue::fake();
        $admin = $this->createWedstrijdleider();

        $this->actingAs($admin)->post(route('beheer.spelers.store'), [
            'name' => 'Test Speler',
            'email' => 'test@example.com',
        ]);

        Queue::assertNotPushed(ImportKnsbRatings::class);
    }

    public function test_update_with_changed_knsb_dispatches_import_job(): void
    {
        Queue::fake();
        $admin = $this->createWedstrijdleider();
        $speler = $this->createPlayer('Bestaand', 1500);

        $this->actingAs($admin)->put(route('beheer.spelers.update', $speler), [
            'name' => $speler->name,
            'email' => $speler->email,
            'knsb_relatienummer' => '99999',
            'auto_participate' => false,
            'show_knsb_rating' => false,
        ]);

        Queue::assertPushed(ImportKnsbRatings::class, function ($job) {
            return $job->user->knsb_relatienummer === '99999';
        });
    }

    public function test_update_without_knsb_change_does_not_dispatch_job(): void
    {
        Queue::fake();
        $admin = $this->createWedstrijdleider();
        $speler = $this->createPlayer('Bestaand', 1500);
        $speler->update(['knsb_relatienummer' => '11111']);

        $this->actingAs($admin)->put(route('beheer.spelers.update', $speler), [
            'name' => 'Nieuwe Naam',
            'email' => $speler->email,
            'knsb_relatienummer' => '11111',
            'auto_participate' => false,
            'show_knsb_rating' => false,
        ]);

        Queue::assertNotPushed(ImportKnsbRatings::class);
    }

    public function test_elo_history_shown_on_player_page(): void
    {
        $player = $this->createPlayer('Alice', 1900, true);

        EloRating::create([
            'user_id' => $player->id,
            'rating' => 1800,
            'source' => 'knsb',
            'measured_at' => '2025-06-01',
        ]);
        EloRating::create([
            'user_id' => $player->id,
            'rating' => 1900,
            'source' => 'manual',
            'measured_at' => '2026-01-01',
        ]);

        $response = $this->get("/ratings/{$player->id}");

        $response->assertStatus(200);
        $response->assertSee('Ratinghistorie');
        $response->assertSee('1800');
        $response->assertSee('1900');
        $response->assertSee('KNSB-rating');
    }
}

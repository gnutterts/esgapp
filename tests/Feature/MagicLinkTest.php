<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $name = 'Alice', string $email = 'alice@example.com'): User
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

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_magic_link_sent_for_valid_email(): void
    {
        Mail::fake();

        $user = $this->createUser();

        $response = $this->post('/login', ['email' => $user->email]);

        // Should redirect to verify page
        $response->assertRedirect(route('login.verify'));

        // A MagicLink record should be created for this user
        $this->assertDatabaseHas('magic_links', [
            'user_id' => $user->id,
        ]);

        $magicLink = MagicLink::where('user_id', $user->id)->first();
        $this->assertNotNull($magicLink);
        $this->assertNull($magicLink->used_at);
        $this->assertTrue($magicLink->expires_at->isFuture());
    }

    public function test_invalid_email_format_returns_error(): void
    {
        $response = $this->from(route('login'))->post('/login', ['email' => 'geengeldigemailadres']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'Dit is geen geldig e-mailadres.',
            $response->getSession()->get('errors')->first('email')
        );
    }

    public function test_empty_email_returns_error(): void
    {
        $response = $this->from(route('login'))->post('/login', ['email' => '']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'Vul je e-mailadres in.',
            $response->getSession()->get('errors')->first('email')
        );
    }

    public function test_unknown_email_returns_error(): void
    {
        $response = $this->from(route('login'))->post('/login', ['email' => 'nobody@example.com']);

        // Redirects back to login with an error (UX over user-enumeration protection)
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'Dit e-mailadres is niet bekend in ons systeem.',
            $response->getSession()->get('errors')->first('email')
        );

        // No magic link should be created
        $this->assertDatabaseCount('magic_links', 0);
    }

    public function test_valid_token_logs_in_user(): void
    {
        $user = $this->createUser();

        $code = '123456';
        MagicLink::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/login/verifieer', ['code' => $code]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Token should be marked as used
        $this->assertDatabaseMissing('magic_links', [
            'token' => hash('sha256', $code),
            'used_at' => null,
        ]);
    }

    public function test_expired_token_rejected(): void
    {
        $user = $this->createUser();

        $code = '654321';
        MagicLink::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $code),
            'expires_at' => now()->subMinutes(5), // expired 5 minutes ago
        ]);

        $response = $this->post('/login/verifieer', ['code' => $code]);

        // Should redirect back to verify with an error
        $response->assertRedirect(route('login.verify'));
        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_used_token_rejected(): void
    {
        $user = $this->createUser();

        $code = '111222';
        MagicLink::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now()->subMinutes(1), // already used
        ]);

        $response = $this->post('/login/verifieer', ['code' => $code]);

        $response->assertRedirect(route('login.verify'));
        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}

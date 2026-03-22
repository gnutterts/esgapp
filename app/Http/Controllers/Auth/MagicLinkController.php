<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function sendLoginCode(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withInput()->withErrors([
                'email' => 'Dit e-mailadres is niet bekend in ons systeem.',
            ]);
        }

        // Invalidate any existing unused codes for this user
        MagicLink::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        MagicLink::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new MagicLinkMail($user, $code));

        return redirect()->route('login.verify')->with('email', $request->email);
    }

    public function showVerifyForm(Request $request): View
    {
        return view('auth.verify', [
            'email' => session('email', ''),
        ]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $hashedCode = hash('sha256', $request->code);

        $magicLink = MagicLink::valid()->where('token', $hashedCode)->first();

        if (! $magicLink) {
            return redirect()->route('login.verify')
                ->with('email', $request->email)
                ->withErrors(['code' => 'Deze code is ongeldig of verlopen.']);
        }

        $magicLink->update(['used_at' => now()]);

        Auth::login($magicLink->user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}

<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstellingenController extends Controller
{
    public function index(): View
    {
        return view('beheer.instellingen.index', [
            'contactEmail' => Setting::get('footer_contact_email', 'wedstrijdleider@esgapp.nl'),
            'toonFooter' => Setting::get('footer_toon_contact', '1'),
            'toonKredit' => Setting::get('footer_toon_kredit', '1'),
            'kreditPrefix' => Setting::get('footer_kredit_prefix', 'Mogelijk gemaakt door'),
            'kreditUrl' => Setting::get('footer_kredit_url', 'https://interioshops.nl'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'footer_contact_email' => ['nullable', 'email', 'max:255'],
            'footer_toon_contact' => ['nullable', 'in:0,1'],
            'footer_toon_kredit' => ['nullable', 'in:0,1'],
            'footer_kredit_prefix' => ['nullable', 'string', 'max:100'],
            'footer_kredit_url' => ['nullable', 'url', 'max:255'],
        ]);

        Setting::set('footer_contact_email', $request->input('footer_contact_email') ?? '');
        Setting::set('footer_toon_contact', $request->boolean('footer_toon_contact') ? '1' : '0');
        Setting::set('footer_toon_kredit', $request->boolean('footer_toon_kredit') ? '1' : '0');
        Setting::set('footer_kredit_prefix', $request->input('footer_kredit_prefix') ?? '');
        Setting::set('footer_kredit_url', $request->input('footer_kredit_url') ?? '');

        return redirect()->route('beheer.instellingen')->with('success', 'Instellingen opgeslagen.');
    }
}

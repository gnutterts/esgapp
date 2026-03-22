@extends('layouts.beheer')

@section('title', $speler->name . ' bewerken — Beheer')

@section('beheer-content')
<div class="mb-6">
    <x-back-link :href="route('beheer.spelers.index')">Terug naar spelers</x-back-link>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Speler bewerken</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $speler->name }}</p>
</div>

@if ($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 rounded-lg text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 max-w-lg">
    <form method="POST" action="{{ route('beheer.spelers.update', $speler) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-input name="name" label="Naam" :value="old('name', $speler->name)" />

        <x-input name="email" type="email" label="E-mailadres" :value="old('email', $speler->email)" />

        <x-input name="knsb_relatienummer" label="KNSB relatienummer"
                 placeholder="bijv. 8012345"
                 :value="old('knsb_relatienummer', $speler->knsb_relatienummer)"
                 helpText="Optioneel. Wordt gebruikt voor het ophalen van KNSB-ratings." />

        <x-input name="elo_rating" type="number" label="ELO-rating"
                 placeholder="standaard: 1200"
                 :value="old('elo_rating', $speler->elo_rating)"
                 helpText="Optioneel. Laat leeg om de standaardwaarde (1200) te gebruiken." />

        <div class="space-y-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_knsb_rating" value="0">
                <input type="checkbox" name="show_knsb_rating" id="show_knsb_rating" value="1"
                       {{ old('show_knsb_rating', $speler->show_knsb_rating) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy dark:text-blue-500 bg-white dark:bg-gray-700 focus:ring-navy dark:focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">KNSB-rating tonen op publieke pagina</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="auto_participate" value="0">
                <input type="checkbox" name="auto_participate" id="auto_participate" value="1"
                       {{ old('auto_participate', $speler->auto_participate) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy dark:text-blue-500 bg-white dark:bg-gray-700 focus:ring-navy dark:focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Automatisch inschrijven voor rondes</span>
            </label>
        </div>

        <div class="pt-2 flex gap-3">
            <x-button type="submit">Wijzigingen opslaan</x-button>
            <x-button variant="secondary" href="{{ route('beheer.spelers.index') }}">Annuleren</x-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Accountstatus</p>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 dark:text-gray-300">
                Huidig:
                <span class="font-medium {{ $speler->is_active ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $speler->is_active ? 'Actief' : 'Inactief' }}
                </span>
            </span>
            <form method="POST" action="{{ route('beheer.spelers.toggle-active', $speler) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Weet je zeker dat je {{ $speler->name }} wilt {{ $speler->is_active ? 'deactiveren' : 'activeren' }}?')"
                        class="text-sm {{ $speler->is_active ? 'text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300' : 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300' }} font-medium underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-navy dark:focus-visible:ring-blue-500">
                    {{ $speler->is_active ? 'Deactiveren' : 'Activeren' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

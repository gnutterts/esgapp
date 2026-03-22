@extends('layouts.beheer')

@section('title', 'Nieuwe speler — Beheer')

@section('beheer-content')
<div class="mb-6">
    <x-back-link :href="route('beheer.spelers.index')">Terug naar spelers</x-back-link>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Nieuwe speler toevoegen</h1>
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
    <form method="POST" action="{{ route('beheer.spelers.store') }}" class="space-y-5">
        @csrf

        <x-input name="name" label="Naam" placeholder="Volledige naam" :value="old('name')" />

        <x-input name="email" type="email" label="E-mailadres" placeholder="speler@voorbeeld.nl" :value="old('email')" />

        <x-input name="knsb_relatienummer" label="KNSB relatienummer"
                 placeholder="bijv. 8012345" :value="old('knsb_relatienummer')"
                 helpText="Optioneel. Wordt gebruikt voor het ophalen van KNSB-ratings." />

        <x-input name="elo_rating" type="number" label="ELO-rating"
                 placeholder="standaard: 1200" :value="old('elo_rating')"
                 helpText="Optioneel. Laat leeg om de standaardwaarde (1200) te gebruiken." />

        <div class="space-y-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="auto_participate" value="0">
                <input type="checkbox" name="auto_participate" value="1"
                       {{ old('auto_participate') ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy dark:text-blue-500 bg-white dark:bg-gray-700 focus:ring-navy dark:focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Auto-deelname inschakelen</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_knsb_rating" value="0">
                <input type="checkbox" name="show_knsb_rating" value="1"
                       {{ old('show_knsb_rating', true) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy dark:text-blue-500 bg-white dark:bg-gray-700 focus:ring-navy dark:focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">KNSB-rating publiek zichtbaar</span>
            </label>
        </div>

        <div class="pt-2 flex gap-3">
            <x-button type="submit">Speler toevoegen</x-button>
            <x-button variant="secondary" href="{{ route('beheer.spelers.index') }}">Annuleren</x-button>
        </div>
    </form>
</div>
@endsection

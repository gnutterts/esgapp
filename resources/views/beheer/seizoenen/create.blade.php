@extends('layouts.beheer')

@section('title', 'Nieuw seizoen — Beheer')

@section('beheer-content')
<div class="mb-6">
    <x-back-link :href="route('beheer.seizoenen.index')">Terug naar seizoenen</x-back-link>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Nieuw seizoen aanmaken</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Na het aanmaken worden automatisch 4 periodes aangemaakt (periode 1: Swiss, periodes 2–4: Keizer).</p>
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
    <form method="POST" action="{{ route('beheer.seizoenen.store') }}" class="space-y-5">
        @csrf

        <x-input name="name" label="Naam seizoen" placeholder="bijv. Seizoen 2025-2026" :value="old('name')" />

        <x-input name="start_date" type="date" label="Startdatum" :value="old('start_date')" />

        <x-input name="end_date" type="date" label="Einddatum" :value="old('end_date')" />

        <div class="pt-2 flex gap-3">
            <x-button type="submit">Seizoen aanmaken</x-button>
            <x-button variant="secondary" href="{{ route('beheer.seizoenen.index') }}">Annuleren</x-button>
        </div>
    </form>
</div>
@endsection

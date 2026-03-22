@extends('layouts.beheer')

@section('title', $seizoen->name . ' bewerken — Beheer')

@section('beheer-content')
<div class="mb-6">
    <x-back-link :href="route('beheer.seizoenen.show', $seizoen)">Terug naar seizoen</x-back-link>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Seizoen bewerken</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $seizoen->name }}</p>
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
    <form method="POST" action="{{ route('beheer.seizoenen.update', $seizoen) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-input name="name" label="Naam seizoen"
                 placeholder="bijv. Seizoen 2025-2026"
                 :value="old('name', $seizoen->name)" />

        <x-input name="start_date" type="date" label="Startdatum"
                 :value="old('start_date', $seizoen->start_date?->format('Y-m-d'))" />

        <x-input name="end_date" type="date" label="Einddatum"
                 :value="old('end_date', $seizoen->end_date?->format('Y-m-d'))" />

        <div class="pt-2 flex gap-3">
            <x-button type="submit">Wijzigingen opslaan</x-button>
            <x-button variant="secondary" href="{{ route('beheer.seizoenen.show', $seizoen) }}">Annuleren</x-button>
        </div>
    </form>
</div>
@endsection

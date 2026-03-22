@extends('layouts.beheer')

@section('title', 'Nieuwe ronde — Beheer')

@section('beheer-content')
<div class="mb-6">
    <a href="{{ route('beheer.rondes.index') }}"
       class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-400 transition-colors mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Terug naar rondes
    </a>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nieuwe ronde aanmaken</h1>
    @if ($seizoen)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Seizoen: <span class="font-medium">{{ $seizoen->name }}</span>.
            Spelers met automatische inschrijving worden direct ingeschreven.
        </p>
    @else
        <p class="mt-1 text-sm text-red-500 dark:text-red-400">Geen actief seizoen gevonden.</p>
    @endif
</div>

@if (! $seizoen)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 text-yellow-800 dark:text-yellow-300 rounded-lg px-5 py-4 text-sm" role="alert">
        Maak eerst een seizoen aan voordat je rondes kunt toevoegen.
        <a href="{{ route('beheer.seizoenen.create') }}" class="font-medium underline ml-1 hover:no-underline">Seizoen aanmaken</a>
    </div>
@else

@if ($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/50 text-red-800 dark:text-red-300 rounded-lg text-sm" role="alert">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 max-w-lg">
    <form method="POST" action="{{ route('beheer.rondes.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="period_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode</label>
            <select name="period_id" id="period_id"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent @error('period_id') border-red-400 dark:border-red-500 @enderror">
                @foreach ($perioden as $periode)
                    <option value="{{ $periode->id }}" {{ old('period_id') == $periode->id ? 'selected' : '' }}>
                        Periode {{ $periode->number }} ({{ $periode->pairing_system === 'swiss' ? 'Swiss' : 'Keizer' }}) — {{ $periode->rounds->count() }} ronde(s)
                    </option>
                @endforeach
            </select>
            @error('period_id')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Datum ronde</label>
            <input type="date" name="date" id="date"
                   value="{{ old('date') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent @error('date') border-red-400 dark:border-red-500 @enderror">
            @error('date')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="registration_deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline inschrijving <span class="font-normal text-gray-400 dark:text-gray-500">(optioneel)</span></label>
            <input type="datetime-local" name="registration_deadline" id="registration_deadline"
                   value="{{ old('registration_deadline') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent @error('registration_deadline') border-red-400 dark:border-red-500 @enderror">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Zonder deadline moet de inschrijving handmatig gesloten worden.</p>
            @error('registration_deadline')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="inline-flex items-center px-5 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                Ronde aanmaken
            </button>
            <a href="{{ route('beheer.rondes.index') }}"
               class="inline-flex items-center px-5 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
                Annuleren
            </a>
        </div>
    </form>
</div>

@endif
@endsection

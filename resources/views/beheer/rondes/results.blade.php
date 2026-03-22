@extends('layouts.beheer')

@section('title', 'Resultaten ronde ' . $ronde->season_round_number . ' — Beheer')

@section('beheer-content')
<div class="mb-6">
    <a href="{{ route('beheer.rondes.show', $ronde) }}"
       class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-400 transition-colors mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Terug naar ronde
    </a>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Resultaten invoeren — Ronde {{ $ronde->season_round_number }}
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $ronde->date->format('d-m-Y') }}</p>
</div>

@if ($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/50 text-red-800 dark:text-red-300 rounded-lg text-sm" role="alert">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('beheer.rondes.store-results', $ronde) }}" class="space-y-6">
    @csrf

    {{-- Partijresultaten --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Partijresultaten</h2>
        </div>

        @if ($ronde->pairings->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Geen partijen gevonden. Genereer eerst een indeling.</p>
        @else
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/30">
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bord</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Wit</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Zwart</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Resultaat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($ronde->pairings->sortBy('board_number') as $partij)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $partij->board_number }}</td>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                @if ($partij->is_bye)
                                    <span class="italic text-gray-400 dark:text-gray-500">BYE</span>
                                @else
                                    {{ $partij->whitePlayer?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                @if (! $partij->is_bye)
                                    {{ $partij->blackPlayer?->name ?? '—' }}
                                @else
                                    {{ $partij->byePlayer?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($partij->is_bye)
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">BYE — geen resultaat</span>
                                    <input type="hidden" name="results[{{ $partij->id }}][result]" value="">
                                @else
                                    <select name="results[{{ $partij->id }}][result]"
                                            class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400">
                                        <option value="">— niet gespeeld —</option>
                                        <option value="1-0" {{ old("results.{$partij->id}.result", $partij->result) === '1-0' ? 'selected' : '' }}>1-0 (Wit wint)</option>
                                        <option value="0-1" {{ old("results.{$partij->id}.result", $partij->result) === '0-1' ? 'selected' : '' }}>0-1 (Zwart wint)</option>
                                        <option value="remise" {{ old("results.{$partij->id}.result", $partij->result) === 'remise' ? 'selected' : '' }}>Remise (½-½)</option>
                                        <option value="*" {{ old("results.{$partij->id}.result", $partij->result) === '*' ? 'selected' : '' }}>Niet gespeeld (*)</option>
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    {{-- Spelersstatussen --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Spelersstatussen</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Stel de status in voor individuele spelers (afwezig, extern, bye).</p>
        </div>

        @php
            $registeredUsers = $ronde->registrations->where('status', 'available')->pluck('user')->filter()->sortBy('name');
            $existingStatuses = $ronde->roundPlayerStatuses->keyBy('user_id');
        @endphp

        @if ($registeredUsers->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Geen ingeschreven spelers.</p>
        @else
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/30">
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Speler</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ext. bevestigd</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($registeredUsers as $user)
                        @php
                            $rps            = $existingStatuses[$user->id] ?? null;
                            $currentStatus  = old("player_status.{$user->id}.status",  $rps->status ?? 'played');
                            $isExtConfirmed = old("player_status.{$user->id}.is_external_confirmed", $rps->is_external_confirmed ?? false);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 js-player-row" data-user-id="{{ $user->id }}">
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">{{ $user->name }}</td>
                            <td class="px-5 py-3">
                                <select name="player_status[{{ $user->id }}][status]"
                                        onchange="toggleExternalConfirm(this)"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 js-status-select">
                                    <option value="played"   {{ $currentStatus === 'played'   ? 'selected' : '' }}>Gespeeld</option>
                                    <option value="absent"   {{ $currentStatus === 'absent'   ? 'selected' : '' }}>Afwezig</option>
                                    <option value="external" {{ $currentStatus === 'external' ? 'selected' : '' }}>Externe partij</option>
                                    <option value="bye"      {{ $currentStatus === 'bye'      ? 'selected' : '' }}>Bye</option>
                                </select>
                            </td>
                            <td class="px-5 py-3 js-external-confirm-cell" style="{{ $currentStatus === 'external' ? '' : 'display:none' }}">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="hidden"
                                           name="player_status[{{ $user->id }}][is_external_confirmed]"
                                           value="0">
                                    <input type="checkbox"
                                           name="player_status[{{ $user->id }}][is_external_confirmed]"
                                           value="1"
                                           {{ $isExtConfirmed ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy dark:text-blue-400 focus:ring-navy dark:focus:ring-blue-400 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Bevestigd</span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button type="submit"
                class="inline-flex items-center px-5 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            Resultaten opslaan
        </button>
        <a href="{{ route('beheer.rondes.show', $ronde) }}"
           class="inline-flex items-center px-5 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            Annuleren
        </a>
    </div>
</form>

<script>
function toggleExternalConfirm(select) {
    var row  = select.closest('.js-player-row');
    var cell = row ? row.querySelector('.js-external-confirm-cell') : null;
    if (cell) {
        cell.style.display = (select.value === 'external') ? '' : 'none';
    }
}
</script>
@endsection

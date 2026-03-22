@extends('layouts.beheer')

@section('title', 'Indeling ronde ' . $ronde->season_round_number . ' — Beheer')

@section('beheer-content')
<div class="mb-6">
    <x-back-link href="{{ route('beheer.rondes.show', $ronde) }}">Terug naar ronde</x-back-link>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">
        Indeling aanpassen — Ronde {{ $ronde->season_round_number }}
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $ronde->period->season->name }} &middot; {{ $ronde->date->format('d-m-Y') }}
    </p>
</div>

@if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg text-sm" role="alert">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $normalPairings = $ronde->pairings->where('is_bye', false)->sortBy('board_number');
    $byePairing     = $ronde->pairings->where('is_bye', true)->first();
    $playerOptions  = $registeredUsers;

    $inputCls  = 'w-14 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400';
    $selectCls = 'w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 min-w-[160px]';
@endphp

<form method="POST" action="{{ route('beheer.rondes.update-pairing', $ronde) }}" id="pairing-form" class="space-y-6">
    @csrf
    @method('PUT')

    {{-- Partijen tabel --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Partijen</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pas de indeling aan door spelers te herplaatsen. Kies wit en zwart per bord.</p>
            </div>
            <button type="button" id="add-row-btn"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-xs font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Bord toevoegen
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700" id="pairings-table">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-16">Bord</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Wit</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20 text-center">Wissel</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Zwart</th>
                        <th scope="col" class="px-4 py-2 w-12"><span class="sr-only">Acties</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="pairings-tbody">
                    @foreach($normalPairings as $index => $partij)
                        <tr class="pairing-row hover:bg-gray-50 dark:hover:bg-gray-800/60" data-index="{{ $index }}">
                            <td class="px-4 py-3">
                                <input type="number"
                                       name="pairings[{{ $index }}][board_number]"
                                       value="{{ $partij->board_number }}"
                                       min="1"
                                       class="{{ $inputCls }}">
                            </td>
                            <td class="px-4 py-3">
                                <select name="pairings[{{ $index }}][white_user_id]" class="{{ $selectCls }}">
                                    <option value="">— geen speler —</option>
                                    @foreach($playerOptions as $player)
                                        <option value="{{ $player->id }}" {{ $partij->white_user_id == $player->id ? 'selected' : '' }}>{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        onclick="swapColors('{{ route('beheer.rondes.swap-colors', [$ronde, $partij]) }}', {{ $partij->board_number }})"
                                        title="Kleuren wisselen"
                                        class="inline-flex items-center justify-center p-1.5 text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy"
                                        aria-label="Kleuren wisselen voor bord {{ $partij->board_number }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <select name="pairings[{{ $index }}][black_user_id]" class="{{ $selectCls }}">
                                    <option value="">— geen speler —</option>
                                    @foreach($playerOptions as $player)
                                        <option value="{{ $player->id }}" {{ $partij->black_user_id == $player->id ? 'selected' : '' }}>{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        onclick="window.pairingRemoveRow(this)"
                                        title="Rij verwijderen"
                                        class="inline-flex items-center justify-center p-1.5 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                        aria-label="Rij verwijderen">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($normalPairings->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic" id="no-pairings-msg">
                Nog geen partijen. Klik op "Bord toevoegen" om een bord toe te voegen.
            </p>
        @endif
    </div>

    {{-- Bye toewijzing --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Bye</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Wijs een bye toe bij een oneven aantal spelers.</p>
        </div>
        <div class="px-5 py-4">
            <x-select label="Speler met bye" name="bye_user_id">
                <option value="">— geen bye —</option>
                @foreach($playerOptions as $player)
                    <option value="{{ $player->id }}" {{ optional($byePairing)->bye_user_id == $player->id ? 'selected' : '' }}>
                        {{ $player->name }}
                    </option>
                @endforeach
            </x-select>
        </div>
    </div>

    {{-- Actieknoppen --}}
    <div class="flex flex-wrap gap-3">
        <x-button type="submit" variant="primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Opslaan
        </x-button>
        <x-button variant="secondary" href="{{ route('beheer.rondes.show', $ronde) }}">Annuleren</x-button>
    </div>
</form>

@if($ronde->status === 'registration_closed' && $ronde->pairings->isNotEmpty())
    <form method="POST" action="{{ route('beheer.rondes.finalize-pairing', $ronde) }}" class="mt-3 flex justify-end">
        @csrf
        <x-button type="submit" variant="primary"
                  onclick="return confirm('Indeling definitief maken? Dit maakt de indeling zichtbaar voor spelers.')"
                  class="bg-purple-700 hover:bg-purple-800 focus-visible:ring-purple-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Indeling definitief maken
        </x-button>
    </form>
@endif

{{-- Overzicht beschikbare spelers --}}
<div class="mt-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">Ingeschreven spelers ({{ $registeredUsers->count() }})</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Alle spelers die zijn ingeschreven voor deze ronde.</p>
    </div>
    @if($registeredUsers->isEmpty())
        <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Geen ingeschreven spelers.</p>
    @else
        <div class="px-5 py-3 flex flex-wrap gap-2">
            @foreach($registeredUsers as $player)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-100 dark:border-blue-700">
                    {{ $player->name }}
                </span>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var playerOptions = @json($playerOptions->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values());

    var inputCls  = 'w-14 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy';
    var selectCls = 'w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-navy min-w-[160px]';

    function buildSelectOptions(selectedId) {
        var html = '<option value="">— geen speler —</option>';
        playerOptions.forEach(function (p) {
            var sel = (selectedId && String(p.id) === String(selectedId)) ? ' selected' : '';
            html += '<option value="' + p.id + '"' + sel + '>' + p.name + '</option>';
        });
        return html;
    }

    function getNextIndex() {
        var rows = document.querySelectorAll('#pairings-tbody .pairing-row');
        if (!rows.length) return 0;
        var max = 0;
        rows.forEach(function (row) {
            var idx = parseInt(row.dataset.index, 10);
            if (idx > max) max = idx;
        });
        return max + 1;
    }

    function getNextBoardNumber() {
        var max = 0;
        document.querySelectorAll('#pairings-tbody .pairing-row').forEach(function (row) {
            var input = row.querySelector('input[type="number"]');
            if (input) { var v = parseInt(input.value, 10); if (v > max) max = v; }
        });
        return max + 1;
    }

    document.getElementById('add-row-btn').addEventListener('click', function () {
        var noMsg = document.getElementById('no-pairings-msg');
        if (noMsg) noMsg.remove();
        var idx   = getNextIndex();
        var board = getNextBoardNumber();
        var tbody = document.getElementById('pairings-tbody');
        var tr = document.createElement('tr');
        tr.className = 'pairing-row hover:bg-gray-50 dark:hover:bg-gray-800/60';
        tr.dataset.index = idx;
        tr.innerHTML =
            '<td class="px-4 py-3"><input type="number" name="pairings[' + idx + '][board_number]" value="' + board + '" min="1" class="' + inputCls + '"></td>' +
            '<td class="px-4 py-3"><select name="pairings[' + idx + '][white_user_id]" class="' + selectCls + '">' + buildSelectOptions(null) + '</select></td>' +
            '<td class="px-4 py-3 text-center"><span class="inline-flex items-center justify-center p-1.5 text-gray-300 dark:text-gray-600" title="Sla eerst op om kleuren te wisselen"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></span></td>' +
            '<td class="px-4 py-3"><select name="pairings[' + idx + '][black_user_id]" class="' + selectCls + '">' + buildSelectOptions(null) + '</select></td>' +
            '<td class="px-4 py-3 text-center"><button type="button" onclick="pairingRemoveRow(this)" title="Rij verwijderen" class="inline-flex items-center justify-center p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors" aria-label="Rij verwijderen"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></td>';
        tbody.appendChild(tr);
    });
})();

function pairingRemoveRow(btn) {
    var row = btn.closest('tr');
    if (row) row.remove();
}

function swapColors(url, boardNumber) {
    if (!confirm('Kleuren wisselen voor bord ' + boardNumber + '?')) return;
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'text/html',
        },
    }).then(function () { window.location.reload(); });
}
</script>
@endpush
@endsection

@extends('layouts.beheer')

@section('title', 'Ronde ' . $ronde->season_round_number . ' — Beheer')

@section('beheer-content')

<div class="mb-6">
    <a href="{{ route('beheer.rondes.index') }}"
       class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-400 transition-colors mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Terug naar rondes
    </a>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Ronde {{ $ronde->season_round_number }}
            <span class="text-gray-400 dark:text-gray-500 text-lg font-normal">— {{ $ronde->date->format('d-m-Y') }}</span>
        </h1>
        @include('partials.round-status-badge', ['status' => $ronde->status])
    </div>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $ronde->period->season->name }} &middot;
        Periode {{ $ronde->period->number }} ({{ $ronde->period->pairing_system }}) &middot;
        Ronde {{ $ronde->round_number }} binnen periode &middot;
        @if($ronde->registration_deadline)
            Deadline: {{ $ronde->registration_deadline->format('d-m-Y H:i') }}
        @else
            Geen deadline (handmatig sluiten)
        @endif
    </p>
</div>

{{-- Action buttons based on status --}}
<div class="mb-6 flex flex-wrap gap-2">
    @if ($ronde->status === 'scheduled')
        <form method="POST" action="{{ route('beheer.rondes.close-registration', $ronde) }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('Weet je zeker dat je de inschrijving wilt sluiten?')"
                    class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yellow-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                Inschrijving sluiten
            </button>
        </form>
    @endif

    @if ($ronde->status === 'registration_closed')
        <form method="POST" action="{{ route('beheer.rondes.generate-pairing', $ronde) }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('Weet je zeker dat je de indeling wilt genereren?')"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                Indeling genereren
            </button>
        </form>
    @endif

    @if ($ronde->status === 'registration_closed' && $ronde->pairings->isNotEmpty())
        <a href="{{ route('beheer.rondes.edit-pairing', $ronde) }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Indeling aanpassen
        </a>
    @endif

    @if ($ronde->status === 'registration_closed' && $ronde->pairings->isNotEmpty())
        <form method="POST" action="{{ route('beheer.rondes.finalize-pairing', $ronde) }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('Indeling definitief maken? Dit maakt de indeling zichtbaar voor spelers.')"
                    class="inline-flex items-center px-4 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                Indeling definitief
            </button>
        </form>
    @endif

    @if ($ronde->status === 'paired')
        <a href="{{ route('beheer.rondes.results', $ronde) }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
            Resultaten invoeren
        </a>
    @endif

    @if ($ronde->status === 'paired')
        <form method="POST" action="{{ route('beheer.rondes.complete', $ronde) }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('Weet je zeker dat je deze ronde wilt afronden?')"
                    class="inline-flex items-center px-4 py-2 bg-gray-700 dark:bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-500 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-700 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                Ronde afronden
            </button>
        </form>
    @endif
</div>

<div class="space-y-6">

    {{-- Inschrijvingen --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                Inschrijvingen ({{ $ronde->registrations->count() + $autoDeelnameSpelers->count() }})
            </h2>
        </div>
        @if ($ronde->registrations->isEmpty() && $autoDeelnameSpelers->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Geen inschrijvingen.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Speler</th>
                            <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($autoDeelnameSpelers->sortBy('name') as $speler)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">{{ $speler->name }}</td>
                            <td class="px-5 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    Beschikbaar (auto)
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($ronde->registrations->sortBy('user.name') as $inschrijving)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">{{ $inschrijving->user->name }}</td>
                            <td class="px-5 py-3 text-sm">
                                @php
                                    $regClass = match($inschrijving->status) {
                                        'available'   => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                        'unavailable' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                                        default       => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                    };
                                    $regLabel = match($inschrijving->status) {
                                        'available'   => 'Ingeschreven',
                                        'unavailable' => 'Afgemeld',
                                        default       => $inschrijving->status,
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $regClass }}">
                                    {{ $regLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    {{-- Indeling --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                Indeling / Partijen ({{ $ronde->pairings->count() }})
            </h2>
        </div>
        @if ($ronde->pairings->isEmpty())
            <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Nog geen indeling gegenereerd.</p>
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
                                    <span class="italic text-gray-400 dark:text-gray-500">BYE — {{ $partij->byePlayer?->name }}</span>
                                @else
                                    {{ $partij->whitePlayer?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                @if (! $partij->is_bye)
                                    {{ $partij->blackPlayer?->name ?? '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $partij->result ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    {{-- Spelersstatussen --}}
    @if ($ronde->roundPlayerStatuses->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Spelersstatussen</h2>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/30">
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Speler</th>
                        <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($ronde->roundPlayerStatuses->sortBy('user.name') as $rps)
                        @php
                            $rpsLabels = [
                                'played'   => ['label' => 'Gespeeld',  'class' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'],
                                'bye'      => ['label' => 'Vrij',      'class' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300'],
                                'absent'   => ['label' => 'Afwezig',   'class' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'],
                                'external' => ['label' => 'Extern',    'class' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'],
                            ];
                            $rpsInfo = $rpsLabels[$rps->status] ?? ['label' => $rps->status, 'class' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'];
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">{{ $rps->user->name }}</td>
                            <td class="px-5 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $rpsInfo['class'] }}">
                                    {{ $rpsInfo['label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif

</div>
@endsection

@extends('layouts.app')

@section('title', 'Uitslag Ronde '.$round->season_round_number)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page header --}}
    <div class="mb-8">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
            Periode {{ $round->period->number }}
            @if($round->period->pairing_system)
                &mdash; {{ ucfirst($round->period->pairing_system) }}
            @endif
            @if($round->period->season)
                &mdash; {{ $round->period->season->name }}
            @endif
        </p>
        <h1 class="text-3xl font-bold text-navy dark:text-white">
            Uitslag Ronde {{ $round->season_round_number }}
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
            <time datetime="{{ $round->date->toDateString() }}">
                {{ $round->date->translatedFormat('l j F Y') }}
            </time>
        </p>
    </div>

    @include('partials.round-navigation', ['routeName' => 'uitslag'])

    {{-- Voorlopig banner --}}
    @if($voorlopig)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700/50 rounded-lg px-5 py-4 mb-6 flex items-start space-x-3" role="alert">
            <svg class="h-5 w-5 text-yellow-500 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-yellow-800 dark:text-yellow-300 font-medium">
                Deze stand is voorlopig. Er zijn nog onbevestigde externe partijen.
            </p>
        </div>
    @endif

    {{-- Section 1: Results --}}
    <div class="mb-10">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Resultaten</h2>

        @if($pairings->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" aria-label="Resultaten ronde {{ $round->season_round_number }}">
                        <thead>
                            <tr class="bg-navy text-white">
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider w-16">Bord</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Wit</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Zwart</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider w-24">Uitslag</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($pairings as $pairing)
                                @php
                                    $whiteWon  = $pairing->result === '1-0';
                                    $blackWon  = $pairing->result === '0-1';
                                    $draw      = $pairing->result === '1/2-1/2';
                                    $uitslagDisplay = match($pairing->result) {
                                        '1-0'     => "1\u{2013}0",
                                        '0-1'     => "0\u{2013}1",
                                        '1/2-1/2' => "\u{00BD}\u{2013}\u{00BD}",
                                        'remise'  => "\u{00BD}\u{2013}\u{00BD}",
                                        default   => $pairing->result ?? "\u{2014}",
                                    };
                                @endphp
                                <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-700/30' : 'bg-white dark:bg-gray-800' }} hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-center">{{ $pairing->board_number }}</td>
                                    <td class="px-4 py-3 {{ $whiteWon ? 'font-bold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $pairing->whitePlayer?->name ?? "\u{2014}" }}
                                    </td>
                                    <td class="px-4 py-3 {{ $blackWon ? 'font-bold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $pairing->blackPlayer?->name ?? "\u{2014}" }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $uitslagDisplay }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bye player --}}
            @if($byePairing && $byePairing->byePlayer)
                <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-lg px-5 py-3 text-sm text-yellow-800 dark:text-yellow-300">
                    <span class="font-semibold">Vrij:</span> {{ $byePairing->byePlayer->name }}
                </div>
            @endif
        @else
            <div class="text-center py-10 text-gray-400 dark:text-gray-500">
                <p>Geen resultaten beschikbaar.</p>
            </div>
        @endif
    </div>

    {{-- Section 2: Standings after this round --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
            Stand na ronde {{ $round->season_round_number }}
            @if($voorlopig)
                <span class="ml-2 text-sm font-normal text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 rounded">voorlopig</span>
            @endif
        </h2>

        @if($standings->isNotEmpty())
            @include('partials.standings-table')
        @else
            <div class="text-center py-10 text-gray-400 dark:text-gray-500">
                <p>Geen standen beschikbaar voor deze ronde.</p>
            </div>
        @endif
    </div>

    {{-- Section 3: Eindstand (filtered by minimum participation) — only on the last round of the season --}}
    @if($isLastRound && $eindstand->count() < $standings->count())
        <div class="mt-10">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                Eindstand na ronde {{ $round->season_round_number }} (minimaal {{ App\Models\Setting::get('min_deelname', 7) }}&times; meegedaan)
                @if($voorlopig)
                    <span class="ml-2 text-sm font-normal text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 rounded">voorlopig</span>
                @endif
            </h2>

            @if($eindstand->isNotEmpty())
                @include('partials.standings-table', ['standings' => $eindstand])
            @else
                <div class="text-center py-10 text-gray-400 dark:text-gray-500">
                    <p>Nog geen spelers met voldoende deelname.</p>
                </div>
            @endif
        </div>
    @endif


</div>
@endsection

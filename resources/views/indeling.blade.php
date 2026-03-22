@extends('layouts.app')

@section('title', 'Indeling Ronde '.$round->season_round_number)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

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
            Indeling Ronde {{ $round->season_round_number }}
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
            <time datetime="{{ $round->date->toDateString() }}">
                {{ $round->date->translatedFormat('l j F Y') }}
            </time>
        </p>
        @if($round->status === 'completed')
            <div class="mt-3">
                <a href="{{ route('uitslag', $round) }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
                    Bekijk uitslag &rarr;
                </a>
            </div>
        @endif
    </div>

    @include('partials.round-navigation', ['routeName' => 'indeling'])

    @if($pairings->isNotEmpty())
        {{-- Pairings table --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" aria-label="Indeling ronde {{ $round->season_round_number }}">
                    <thead>
                        <tr class="bg-navy text-white">
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider w-16">Bord</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Wit</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Zwart</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($pairings as $pairing)
                            <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-700/30' : 'bg-white dark:bg-gray-800' }} hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                <td class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-center">{{ $pairing->board_number }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">
                                    {{ $pairing->whitePlayer?->name ?? "\u{2014}" }}
                                </td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">
                                    {{ $pairing->blackPlayer?->name ?? "\u{2014}" }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bye player --}}
        @if($byePairing && $byePairing->byePlayer)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-lg px-5 py-3 text-sm text-yellow-800 dark:text-yellow-300">
                <span class="font-semibold">Vrij:</span> {{ $byePairing->byePlayer->name }}
            </div>
        @endif

    @else
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <svg class="h-16 w-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-lg">Geen indeling beschikbaar voor deze ronde.</p>
        </div>
    @endif

    <div class="mt-8 text-center">
        <a href="{{ route('home') }}" class="text-sm text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">&larr; Terug naar home</a>
    </div>

</div>
@endsection

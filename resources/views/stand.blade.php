@extends('layouts.app')

@section('title', $latestCompletedRound ? 'Stand na ronde '.$latestCompletedRound->season_round_number : 'Actuele Stand')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-navy dark:text-white">
            @if($latestCompletedRound)
                Stand na ronde {{ $latestCompletedRound->season_round_number }}
            @else
                Actuele Stand
            @endif
        </h1>
        @if($currentSeason)
            <p class="text-gray-500 dark:text-gray-400 mt-1">Seizoen: {{ $currentSeason->name }}</p>
        @endif
    </div>

    @if($standings->isNotEmpty())
        @include('partials.standings-table')

        @if($latestCompletedRound)
            <div class="mt-4 text-right">
                <a href="{{ route('uitslag', $latestCompletedRound) }}" class="text-sm text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">Bekijk uitslag van ronde {{ $latestCompletedRound->season_round_number }} &rarr;</a>
            </div>
        @endif

        @if($isLastRound && $eindstand->count() < $standings->count())
            <div class="mt-10">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Eindstand (minimaal {{ App\Models\Setting::get('min_deelname', 7) }}&times; meegedaan)</h2>
                @include('partials.standings-table', ['standings' => $eindstand])
            </div>
        @endif

    @elseif(! $currentSeason)
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <p class="text-lg">Er is nog geen seizoen actief.</p>
        </div>
    @else
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <svg class="h-16 w-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-lg">Nog geen standen beschikbaar.</p>
        </div>
    @endif

</div>
@endsection

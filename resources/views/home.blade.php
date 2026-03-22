@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Hero / Welcome --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-navy dark:text-white mb-3">ESGapp</h1>
        @if($currentSeason)
            <p class="text-gray-500 dark:text-gray-400 text-lg">Seizoen: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $currentSeason->name }}</span></p>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-lg">Welkom bij de interne schaakcompetitie van ESG.</p>
        @endif
    </div>

    @if(! $currentSeason)
        <div class="text-center py-16 text-gray-500 dark:text-gray-400">
            <svg class="h-16 w-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xl font-medium text-gray-600 dark:text-gray-300">Er is nog geen seizoen actief.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            {{-- Standings card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-navy px-6 py-4 flex items-center justify-between">
                    <h2 class="text-white font-semibold text-lg">
                        @if($latestCompletedRound)
                            Stand na ronde {{ $latestCompletedRound->season_round_number }}
                        @else
                            Huidige Stand
                        @endif
                    </h2>
                    <a href="{{ route('stand') }}" class="text-blue-200 hover:text-white text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">
                        Volledige stand &rarr;
                    </a>
                </div>

                @if($standings->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" aria-label="Competitiestand (top)">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">Pos</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">+/-</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Speler</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Punten</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($standings as $standing)
                                    <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-700/30' : 'bg-white dark:bg-gray-800' }}">
                                        <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">{{ $standing->position }}</td>
                                        <td class="px-3 py-2">
                                            @if($standing->position_change > 0)
                                                <span class="text-green-600 dark:text-green-400 font-bold" title="Gestegen met {{ $standing->position_change }}" aria-label="Gestegen met {{ $standing->position_change }}">&#9650;</span>
                                            @elseif($standing->position_change < 0)
                                                <span class="text-red-500 dark:text-red-400 font-bold" title="Gedaald met {{ abs($standing->position_change) }}" aria-label="Gedaald met {{ abs($standing->position_change) }}">&#9660;</span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500" aria-label="Geen wijziging">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $standing->user->name }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200">{{ number_format($standing->points, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 text-right">
                        <a href="{{ route('stand') }}" class="text-sm text-navy dark:text-blue-400 hover:underline font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
                            Alle spelers bekijken &rarr;
                        </a>
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                        <svg class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm">Nog geen standen beschikbaar.</p>
                    </div>
                @endif
            </div>

            {{-- Next round(s) card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-navy px-6 py-4">
                    <h2 class="text-white font-semibold text-lg">Volgende Ronde{{ $nextRounds->count() > 1 ? 's' : '' }}</h2>
                </div>

                @if($nextRounds->isNotEmpty())
                    @foreach($nextRounds as $nextRound)
                        @auth
                            <a href="{{ route('dashboard') }}#aankomende-rondes"
                               class="block px-6 py-6 {{ ! $loop->last ? 'border-b-4 border-gray-100 dark:border-gray-700' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                        @else
                            <div class="px-6 py-6 {{ ! $loop->last ? 'border-b-4 border-gray-100 dark:border-gray-700' : '' }}">
                        @endauth
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 bg-navy text-white rounded-lg p-3 text-center min-w-[56px]" aria-hidden="true">
                                    <span class="block text-2xl font-bold leading-none">{{ $nextRound->date->format('d') }}</span>
                                    <span class="block text-xs uppercase tracking-wide mt-1">{{ $nextRound->date->translatedFormat('M') }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 @auth group-hover:text-navy dark:group-hover:text-blue-300 transition-colors @endauth">
                                            Ronde {{ $nextRound->season_round_number }}
                                        </h3>
                                        @if(in_array($nextRound->status, ['paired', 'completed']))
                                            <span class="text-sm text-navy dark:text-blue-400 whitespace-nowrap">
                                                Bekijk indeling &rarr;
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Periode {{ $nextRound->period->number }}
                                        @if($nextRound->period->pairing_system)
                                            &mdash; {{ ucfirst($nextRound->period->pairing_system) }}
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                        <time datetime="{{ $nextRound->date->toDateString() }}">
                                            {{ $nextRound->date->translatedFormat('l j F Y') }}
                                        </time>
                                    </p>
                                    <div class="flex items-center justify-between mt-2 text-sm">
                                        @php
                                            $statusLabels = [
                                                'scheduled'           => 'Gepland',
                                                'registration_closed' => 'Inschrijving gesloten',
                                                'paired'              => 'Indeling beschikbaar',
                                                'completed'           => 'Afgerond',
                                            ];
                                            $statusColors = [
                                                'scheduled'           => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                'registration_closed' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                'paired'              => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                'completed'           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                            ];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$nextRound->status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                            {{ $statusLabels[$nextRound->status] ?? $nextRound->status }}
                                        </span>
                                        @if($nextRound->registration_deadline && $nextRound->status === 'scheduled')
                                            <span class="text-gray-500 dark:text-gray-400">
                                                Inschrijving sluit op
                                                <time datetime="{{ $nextRound->registration_deadline->toIso8601String() }}" class="font-medium text-gray-700 dark:text-gray-200">
                                                    {{ $nextRound->registration_deadline->translatedFormat('d M Y, H:i') }}
                                                </time>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @auth
                            </a>
                        @else
                            </div>
                        @endauth
                    @endforeach
                @else
                    <div class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                        <svg class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm">Geen komende ronde gepland.</p>
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- Call to action for guests --}}
    @guest
        <div class="mt-10 text-center">
            <p class="text-gray-500 dark:text-gray-400 mb-4">Ben je speler? Log in om je aanwezigheid op te geven.</p>
            <a href="/login"
               class="inline-block bg-navy hover:bg-navy-light text-white font-medium px-6 py-3 rounded-lg transition-colors shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-navy dark:focus-visible:ring-offset-gray-900">
                Inloggen
            </a>
        </div>
    @endguest

</div>
@endsection

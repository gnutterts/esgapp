{{--
    Round navigation: grouped period/round picker + prev/next arrows.

    Required variables:
      $round              — current Round model
      $allRounds          — Collection of Period models (each with ->rounds relation loaded)
      $routeName          — 'indeling' or 'uitslag'
      $previousRound      — Round|null
      $nextRound          — Round|null

    Optional:
      $accessibleStatuses — array of statuses that are navigable (default: ['paired','completed'] for indeling, ['completed'] for uitslag)
--}}
@php
    $accessibleStatuses ??= ($routeName === 'uitslag' ? ['completed'] : ['paired', 'completed']);
@endphp

<nav aria-label="Ronde navigatie" class="mb-6">

    {{-- Period/round grid --}}
    @if($allRounds->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-3">
            @foreach($allRounds as $period)
                <div class="flex items-stretch border-b border-gray-100 dark:border-gray-700 last:border-b-0">

                    {{-- Period label --}}
                    <div class="flex-shrink-0 w-28 sm:w-36 flex flex-col justify-center px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-r border-gray-200 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Periode {{ $period->number }}</span>
                        @if($period->pairing_system)
                            <span class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ $period->pairing_system }}</span>
                        @endif
                    </div>

                    {{-- Round buttons --}}
                    <div class="flex flex-wrap gap-1 items-center px-2 py-2">
                        @foreach($period->rounds->sortBy('season_round_number') as $r)
                            @php
                                $isCurrent   = $r->id === $round->id;
                                $isAccessible = in_array($r->status, $accessibleStatuses);
                            @endphp

                            @if($isCurrent)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded text-xs font-bold bg-navy text-white dark:bg-navy-light dark:text-white"
                                      aria-current="page"
                                      title="Ronde {{ $r->season_round_number }} (huidige pagina)">
                                    {{ $r->season_round_number }}
                                </span>
                            @elseif($isAccessible)
                                <a href="{{ route($routeName, $r) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded text-xs font-semibold text-navy dark:text-blue-400 bg-gray-100 dark:bg-gray-700 hover:bg-navy hover:text-white dark:hover:bg-blue-600 dark:hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400"
                                   title="Ronde {{ $r->season_round_number }}">
                                    {{ $r->season_round_number }}
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded text-xs text-gray-300 dark:text-gray-600 cursor-default"
                                      title="Ronde {{ $r->season_round_number }} (nog niet beschikbaar)">
                                    {{ $r->season_round_number }}
                                </span>
                            @endif
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- Prev/next arrows --}}
    @if($previousRound || $nextRound)
        <div class="flex items-center justify-between text-sm">
            <div>
                @if($previousRound)
                    <a href="{{ route($routeName, $previousRound) }}"
                       class="inline-flex items-center gap-1 text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy rounded">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Ronde {{ $previousRound->season_round_number }}
                    </a>
                @endif
            </div>
            <div>
                @if($nextRound)
                    <a href="{{ route($routeName, $nextRound) }}"
                       class="inline-flex items-center gap-1 text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy rounded">
                        Ronde {{ $nextRound->season_round_number }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    @endif

</nav>

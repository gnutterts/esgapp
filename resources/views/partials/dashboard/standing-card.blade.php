{{-- Variables: $userStanding, $latestCompletedRound, $user --}}
@if($userStanding)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5">
        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Jouw stand</h2>
        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex flex-wrap items-center gap-1.5">
            #{{ $userStanding->position }}
            <span class="text-gray-400 font-normal">&mdash;</span>
            <span class="text-base text-gray-600 dark:text-gray-300">{{ number_format($userStanding->points, 1) }} punten</span>
            @if($latestCompletedRound)
                <span class="text-sm text-gray-400 dark:text-gray-500 font-normal">(na ronde {{ $latestCompletedRound->season_round_number }})</span>
            @endif
        </p>
        <a href="{{ route('stand') }}"
           class="text-sm text-navy dark:text-blue-400 hover:underline mt-1 inline-block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy rounded">
            Volledige stand bekijken
        </a>
    </div>
@endif

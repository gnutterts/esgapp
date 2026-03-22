{{-- Variables: $season, $currentPeriod --}}
@if($season && $currentPeriod)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5">
        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Huidige periode</h2>
        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex flex-wrap items-center gap-2">
            Periode {{ $currentPeriod->number }}
            <span class="text-gray-400 font-normal">&mdash;</span>
            <span class="text-base text-gray-600 dark:text-gray-300">
                Ronde {{ $currentPeriod->rounds->whereIn('status', ['scheduled','registration_closed','paired'])->first()?->round_number ?? $currentPeriod->rounds->count() }}
                van {{ $currentPeriod->rounds->count() }}
            </span>
            <x-badge color="blue">{{ ucfirst(str_replace('_', '-', $currentPeriod->pairing_system)) }}</x-badge>
        </p>
    </div>
@elseif(!$season)
    <div class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 rounded-xl p-5 text-sm" role="alert">
        Er is momenteel geen actief seizoen.
    </div>
@endif

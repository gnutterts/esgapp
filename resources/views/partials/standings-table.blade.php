{{-- Standings table with legend --}}
{{-- Required: $standings (collection of Standing models with user relation) --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" aria-label="Klassement">
            <thead>
                <tr class="bg-navy dark:bg-navy-dark text-white">
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider w-10">Pos</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider w-10" aria-label="Positieverandering">+/&minus;</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider">Speler</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider">Punten</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Partijen gespeeld">p</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Kleurbalans (wit minus zwart)">k</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Gewonnen">g</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Remise">r</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Verloren">v</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Externe partij">e</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Vrij (bye)">o</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider hidden md:table-cell" title="Afwezig">a</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($standings as $standing)
                    <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-white dark:bg-gray-900' }} hover:bg-blue-50 dark:hover:bg-navy/20 transition-colors">
                        <td class="px-3 py-3 font-semibold text-gray-700 dark:text-gray-300">{{ $standing->position }}</td>
                        <td class="px-3 py-3">
                            @if($standing->position_change > 0)
                                <span class="text-green-600 dark:text-green-400 font-bold" title="Gestegen met {{ $standing->position_change }}" aria-label="Gestegen met {{ $standing->position_change }}">&#9650;</span>
                            @elseif($standing->position_change < 0)
                                <span class="text-red-500 dark:text-red-400 font-bold" title="Gedaald met {{ abs($standing->position_change) }}" aria-label="Gedaald met {{ abs($standing->position_change) }}">&#9660;</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600" aria-label="Ongewijzigd">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $standing->user->name }}</td>
                        <td class="px-3 py-3 text-right font-bold text-gray-800 dark:text-gray-100">{{ number_format($standing->points, 1) }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->games_played }}</td>
                        <td class="px-3 py-3 text-right hidden md:table-cell">
                            @if($standing->color_balance > 0)
                                <span class="text-blue-600 dark:text-blue-400">+{{ $standing->color_balance }}</span>
                            @elseif($standing->color_balance < 0)
                                <span class="text-orange-600 dark:text-orange-400">{{ $standing->color_balance }}</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->wins }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->draws }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->losses }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->external_count }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->bye_count }}</td>
                        <td class="px-3 py-3 text-right text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ $standing->absence_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="md:hidden px-4 py-2 text-xs text-gray-400 dark:text-gray-500 text-center border-t border-gray-100 dark:border-gray-700">
        Draai je scherm voor meer statistieken
    </div>
</div>

{{-- Legend --}}
<div class="hidden md:block bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-lg px-6 py-4">
    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Legenda</h3>
    <dl class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-x-6 gap-y-1 text-sm">
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">p</dt>
            <dd class="text-gray-500 dark:text-gray-400">Partijen gespeeld</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">k</dt>
            <dd class="text-gray-500 dark:text-gray-400">Kleurbalans (wit &minus; zwart)</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">g</dt>
            <dd class="text-gray-500 dark:text-gray-400">Gewonnen</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">r</dt>
            <dd class="text-gray-500 dark:text-gray-400">Remise</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">v</dt>
            <dd class="text-gray-500 dark:text-gray-400">Verloren</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">e</dt>
            <dd class="text-gray-500 dark:text-gray-400">Externe partij</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">o</dt>
            <dd class="text-gray-500 dark:text-gray-400">Vrij (bye)</dd>
        </div>
        <div class="flex items-center gap-2">
            <dt class="font-semibold text-gray-700 dark:text-gray-300 w-5">a</dt>
            <dd class="text-gray-500 dark:text-gray-400">Afwezig</dd>
        </div>
    </dl>
</div>

@extends('layouts.beheer')

@section('title', $seizoen->name . ' — Beheer')

@section('beheer-content')
<div class="mb-6">
    <a href="{{ route('beheer.seizoenen.index') }}"
       class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-400 transition-colors mb-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Terug naar seizoenen
    </a>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $seizoen->name }}</h1>
        @if ($seizoen->is_current)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                Huidig seizoen
            </span>
        @endif
        <a href="{{ route('beheer.seizoenen.edit', $seizoen) }}"
           class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400">
            Bewerken
        </a>
        <form method="POST" action="{{ route('beheer.seizoenen.destroy', $seizoen) }}" class="inline"
              onsubmit="return confirm('Weet je zeker dat je seizoen \'{{ addslashes($seizoen->name) }}\' wilt verwijderen? Alle periodes, rondes, uitslagen en indelingen worden ook verwijderd.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 text-xs font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                Verwijderen
            </button>
        </form>
    </div>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $seizoen->start_date->format('d-m-Y') }} — {{ $seizoen->end_date->format('d-m-Y') }}
    </p>
</div>

<div class="space-y-6">
    @foreach ($seizoen->periods->sortBy('number') as $periode)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                        Periode {{ $periode->number }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Systeem: <span class="font-medium capitalize">{{ $periode->pairing_system }}</span>
                        &middot; Rondes {{ ($periode->number - 1) * 6 + 1 }}–{{ $periode->number * 6 }}
                    </p>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $periode->rounds->count() }} / 6 rondes aangemaakt</span>
            </div>

            @if ($periode->rounds->isEmpty())
                <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Nog geen rondes voor deze periode.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/30">
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ronde</th>
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Datum</th>
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($periode->rounds as $ronde)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                        Ronde {{ $ronde->season_round_number }}
                                        <span class="text-gray-400 dark:text-gray-500">(#{{ $ronde->round_number }} in periode)</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $ronde->date->format('d-m-Y') }}</td>
                                    <td class="px-5 py-3 text-sm">
                                        @include('partials.round-status-badge', ['status' => $ronde->status])
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm">
                                        <a href="{{ route('beheer.rondes.show', $ronde) }}"
                                           class="text-navy dark:text-blue-400 hover:underline font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">Beheren</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endsection

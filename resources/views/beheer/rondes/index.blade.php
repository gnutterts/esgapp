@extends('layouts.beheer')

@section('title', 'Rondes — Beheer')

@section('beheer-content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rondes</h1>
        @if ($seizoen)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Huidig seizoen: <span class="font-medium">{{ $seizoen->name }}</span></p>
        @endif
    </div>
    <a href="{{ route('beheer.rondes.create') }}"
       class="inline-flex items-center px-4 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nieuwe ronde
    </a>
</div>

@if (! $seizoen)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 text-yellow-800 dark:text-yellow-300 rounded-lg px-5 py-4 text-sm" role="alert">
        Geen actief seizoen gevonden. <a href="{{ route('beheer.seizoenen.create') }}" class="font-medium underline hover:no-underline">Maak eerst een seizoen aan.</a>
    </div>
@else

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
                        </p>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $periode->rounds->count() }} / 6 rondes</span>
                </div>

                @if ($periode->rounds->isEmpty())
                    <p class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500 italic">Nog geen rondes voor deze periode.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/30">
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Seizoensronde</th>
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Datum</th>
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deadline inschrijving</th>
                                <th scope="col" class="px-5 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th scope="col" class="px-5 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($periode->rounds->sortBy('season_round_number') as $ronde)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                                        Ronde {{ $ronde->season_round_number }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $ronde->date->format('d-m-Y') }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        @if($ronde->registration_deadline)
                                            {{ $ronde->registration_deadline->format('d-m-Y H:i') }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 italic">Handmatig</span>
                                        @endif
                                    </td>
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
@endif
@endsection

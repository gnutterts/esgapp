@extends('layouts.beheer')

@section('title', 'Spelers — Beheer')

@section('beheer-content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Spelers</h1>
    <x-button href="{{ route('beheer.spelers.create') }}">
        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nieuwe speler
    </x-button>
</div>

{{-- Filter tabs --}}
@php
    $filter        = request('filter', 'actief');
    $activeCount   = $spelers->where('is_active', true)->count();
    $inactiveCount = $spelers->where('is_active', false)->count();
    $filtered      = $filter === 'inactief' ? $spelers->where('is_active', false) : $spelers->where('is_active', true);
@endphp

<div class="flex gap-1 mb-4 border-b border-gray-200 dark:border-gray-700" role="tablist">
    <a href="{{ route('beheer.spelers.index', ['filter' => 'actief']) }}"
       role="tab"
       aria-selected="{{ $filter !== 'inactief' ? 'true' : 'false' }}"
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
              {{ $filter !== 'inactief'
                  ? 'border-navy text-navy dark:border-blue-400 dark:text-blue-400'
                  : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
        Actief ({{ $activeCount }})
    </a>
    <a href="{{ route('beheer.spelers.index', ['filter' => 'inactief']) }}"
       role="tab"
       aria-selected="{{ $filter === 'inactief' ? 'true' : 'false' }}"
       class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
              {{ $filter === 'inactief'
                  ? 'border-navy text-navy dark:border-blue-400 dark:text-blue-400'
                  : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
        Inactief ({{ $inactiveCount }})
    </a>
</div>

{{-- Search --}}
<div class="mb-4">
    <label for="speler-zoeken" class="sr-only">Zoek speler</label>
    <input type="text"
           id="speler-zoeken"
           placeholder="Zoek op naam of e-mail..."
           class="w-full sm:w-72 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-500 focus:border-navy dark:focus:border-blue-500">
</div>

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
    @if ($filtered->isEmpty())
        <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="font-medium">Geen spelers gevonden.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table id="speler-tabel" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Naam</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">E-mail</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ELO</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">KNSB</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Auto-inschrijving</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($filtered->sortBy('name') as $speler)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ ! $speler->is_active ? 'opacity-60' : '' }}">
                        <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $speler->name }}</td>
                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $speler->email }}</td>
                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                            <span class="capitalize">{{ $speler->role }}</span>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ $speler->elo_rating ?? '—' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                            @if($speler->knsb_relatienummer)
                                <a href="https://ratingviewer.nl/player/{{ $speler->knsb_relatienummer }}"
                                   target="_blank" rel="noopener"
                                   class="text-navy dark:text-blue-400 hover:underline">{{ $speler->knsb_relatienummer }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-4 text-sm">
                            @if ($speler->auto_participate)
                                <x-badge color="green">Ja</x-badge>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">Nee</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('beheer.spelers.edit', $speler) }}"
                                   class="text-navy dark:text-blue-400 hover:underline font-medium">Bewerken</a>
                                <form method="POST" action="{{ route('beheer.spelers.toggle-active', $speler) }}">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Weet je zeker dat je {{ $speler->name }} wilt {{ $speler->is_active ? 'deactiveren' : 'activeren' }}?')"
                                            class="text-sm {{ $speler->is_active ? 'text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300' : 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300' }} font-medium">
                                        {{ $speler->is_active ? 'Deactiveren' : 'Activeren' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('speler-zoeken')?.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#speler-tabel tbody tr').forEach(function (row) {
            const naam  = row.children[0]?.textContent.toLowerCase() || '';
            const email = row.children[1]?.textContent.toLowerCase() || '';
            row.style.display = (naam.includes(query) || email.includes(query)) ? '' : 'none';
        });
    });
</script>
@endpush

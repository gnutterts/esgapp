{{-- Variables: $upcomingRounds, $registrations, $user --}}
<div id="aankomende-rondes" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Aankomende rondes</h2>
    </div>

    @if($upcomingRounds->isEmpty())
        <div class="px-5 py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
            Er zijn geen aankomende rondes gepland.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700" aria-label="Aankomende rondes">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Datum</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ronde</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden sm:table-cell">Periode</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden lg:table-cell">Deadline</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Beschikbaarheid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($upcomingRounds as $round)
                        @php
                            $reg = $registrations->get($round->id);
                            $isAvailable = $reg && $reg->status === 'available';
                            $isUnavailable = $reg && $reg->status === 'unavailable';
                            $noRegistration = !$reg;
                            $isVirtuallyAvailable = $noRegistration && $user->auto_participate;

                            $canToggle = $round->status === 'scheduled'
                                && (!$round->registration_deadline || now()->isBefore($round->registration_deadline));
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $round->date?->translatedFormat('D d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                                Ronde {{ $round->season_round_number }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap hidden sm:table-cell">
                                Periode {{ $round->period->number }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap hidden lg:table-cell">
                                @if($round->registration_deadline)
                                    {{ $round->registration_deadline->translatedFormat('D d M Y, H:i') }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Geen</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @include('partials.round-status-badge', ['status' => $round->status])
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($canToggle)
                                    <form method="POST" action="{{ route('registration.toggle', $round) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1
                                                    {{ ($isAvailable || $isVirtuallyAvailable)
                                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800/50 border border-green-300 dark:border-green-700 focus-visible:ring-green-500'
                                                        : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800/50 border border-red-300 dark:border-red-700 focus-visible:ring-red-500' }}">
                                            @if($isAvailable || $isVirtuallyAvailable)
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Beschikbaar{{ $isVirtuallyAvailable ? ' (auto)' : '' }}
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $noRegistration ? 'Niet opgegeven' : 'Niet beschikbaar' }}
                                            @endif
                                        </button>
                                    </form>
                                @else
                                    @if($isAvailable || $isVirtuallyAvailable)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-700">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Beschikbaar{{ $isVirtuallyAvailable ? ' (auto)' : '' }}
                                        </span>
                                    @elseif($isUnavailable)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300 border border-red-200 dark:border-red-700">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Niet beschikbaar
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                                            Niet opgegeven
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

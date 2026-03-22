{{-- Variables: $recentRounds, $pairings, $recentStandings, $prevRoundIds, $prevStandings --}}
@if($recentRounds->isNotEmpty())
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Recente rondes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700" aria-label="Recente rondes">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Datum</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ronde</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tegenstander</th>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Uitslag</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Punten</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentRounds as $round)
                        @php
                            $pairing = $pairings->get($round->id);
                            $userId  = auth()->id();

                            // Uitslag label
                            $uitslagLabel = null;
                            $uitslagClass = 'text-gray-400 dark:text-gray-500';
                            $opponentName = null;

                            if ($pairing) {
                                if ($pairing->is_bye) {
                                    $uitslagLabel = 'Bye';
                                    $uitslagClass = 'text-yellow-600 dark:text-yellow-400';
                                } else {
                                    $isWhite = $pairing->white_user_id === $userId;
                                    $opponentName = $isWhite
                                        ? $pairing->blackPlayer?->name
                                        : $pairing->whitePlayer?->name;

                                    if ($pairing->result !== null) {
                                        $result = $pairing->result;
                                        if ($result === '1-0') {
                                            $uitslagLabel = $isWhite ? 'Gewonnen' : 'Verloren';
                                            $uitslagClass = $isWhite
                                                ? 'text-green-700 dark:text-green-400 font-semibold'
                                                : 'text-red-600 dark:text-red-400';
                                        } elseif ($result === '0-1') {
                                            $uitslagLabel = $isWhite ? 'Verloren' : 'Gewonnen';
                                            $uitslagClass = $isWhite
                                                ? 'text-red-600 dark:text-red-400'
                                                : 'text-green-700 dark:text-green-400 font-semibold';
                                        } elseif ($result === '1/2-1/2' || $result === 'remise' || str_contains($result, '½')) {
                                            $uitslagLabel = 'Remise';
                                            $uitslagClass = 'text-yellow-600 dark:text-yellow-400';
                                        } else {
                                            $uitslagLabel = $result;
                                        }
                                    }
                                }
                            }

                            // Keizer-punten verdiend in deze ronde
                            $standing     = $recentStandings->get($round->id);
                            $prevRoundId  = $prevRoundIds[$round->id] ?? null;
                            $prevStanding = $prevRoundId ? $prevStandings->get($prevRoundId) : null;

                            if ($standing) {
                                $roundPoints = $prevStanding
                                    ? round($standing->points - $prevStanding->points, 1)
                                    : round($standing->points, 1);
                                $pointsLabel = number_format($roundPoints, 1);
                                $pointsClass = $roundPoints > 0
                                    ? 'text-gray-800 dark:text-gray-100 font-semibold'
                                    : 'text-gray-400 dark:text-gray-500';
                            } else {
                                $pointsLabel = '—';
                                $pointsClass = 'text-gray-400 dark:text-gray-500';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $round->date?->translatedFormat('D d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                                <a href="{{ route('uitslag', $round) }}" class="hover:underline text-navy dark:text-blue-400">
                                    Ronde {{ $round->season_round_number }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $opponentName ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-sm whitespace-nowrap {{ $uitslagClass }}">
                                {{ $uitslagLabel ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-right whitespace-nowrap {{ $pointsClass }}">
                                {{ $pointsLabel }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

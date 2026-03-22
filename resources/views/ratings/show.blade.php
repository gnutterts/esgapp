@extends('layouts.app')

@section('title', $speler->name . ' — Ratings')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('ratings') }}"
       class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-navy dark:hover:text-blue-400 transition-colors mb-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">
        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Terug naar ratings
    </a>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-navy dark:text-white">{{ $speler->name }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            @if($speler->elo_rating !== null && $speler->elo_rating !== 1200)
                <span class="font-medium text-lg text-gray-800 dark:text-gray-200">KNSB: {{ $speler->elo_rating }}</span>
            @endif
            @if($speler->knsb_relatienummer)
                <a href="https://ratingviewer.nl/player/{{ $speler->knsb_relatienummer }}"
                   target="_blank" rel="noopener"
                   class="text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">KNSB {{ $speler->knsb_relatienummer }}</a>
            @endif
        </div>
    </div>

    @if($knsbRatings->isNotEmpty())
        @php
            $knsbPoints = $knsbRatings->map(fn ($r) => [
                'x' => $r->measured_at->timestamp * 1000,
                'y' => $r->rating,
            ])->values();
        @endphp
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Ratinghistorie</h2>
                <button id="resetZoom"
                        style="display:none"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Zoom herstellen
                </button>
            </div>
            <div class="p-4 sm:p-6">
                <style>#ratingChart { cursor: grab; } #ratingChart:active { cursor: grabbing; }</style>
                <canvas id="ratingChart" role="img" aria-label="Grafiek van ratinghistorie voor {{ $speler->name }}"></canvas>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/date-fns@3/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/hammerjs@2/hammer.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2/dist/chartjs-plugin-zoom.min.js"></script>
        <script>
            (function () {
                var isDark = document.documentElement.classList.contains('dark');

                var knsbPoints = @json($knsbPoints);

                var tooltipBg = isDark ? '#1e293b' : '#1e3a5f';
                var tickColor = isDark ? '#9ca3af' : '#6b7280';
                var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';

                // Dutch month formatter for axis ticks
                var nlMonths = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
                function fmtTick(ts) {
                    var d = new Date(ts);
                    return nlMonths[d.getMonth()] + ' \'' + String(d.getFullYear()).slice(2);
                }

                // Dutch date formatter for tooltip title
                var nlMonthsFull = ['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'];
                function fmtTooltip(ts) {
                    var d = new Date(ts);
                    return d.getDate() + ' ' + nlMonthsFull[d.getMonth()] + ' ' + d.getFullYear();
                }

                var knsbColor = isDark ? '#60a5fa' : '#1e3a5f';
                var knsbFill  = isDark ? 'rgba(96, 165, 250, 0.08)' : 'rgba(30, 58, 95, 0.06)';

                var resetBtn = document.getElementById('resetZoom');
                var chart = new Chart(document.getElementById('ratingChart'), {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'KNSB-rating',
                            data: knsbPoints,
                            borderColor: knsbColor,
                            backgroundColor: knsbFill,
                            borderWidth: 2.5,
                            pointBackgroundColor: knsbColor,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.25,
                            spanGaps: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 2.2,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleFont: { size: 13 },
                                bodyFont: { size: 13 },
                                padding: 10,
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    title: function (items) {
                                        return items.length ? fmtTooltip(items[0].parsed.x) : '';
                                    },
                                },
                            },
                            zoom: {
                                pan: {
                                    enabled: true,
                                    mode: 'x',
                                    onPanComplete: function () {
                                        resetBtn.style.display = 'inline-flex';
                                    },
                                },
                                zoom: {
                                    wheel: { enabled: true },
                                    pinch: { enabled: true },
                                    mode: 'x',
                                    onZoomComplete: function () {
                                        resetBtn.style.display = 'inline-flex';
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'month',
                                    tooltipFormat: 'T',
                                },
                                grid: { display: false },
                                ticks: {
                                    color: tickColor,
                                    font: { size: 11 },
                                    maxRotation: 45,
                                    autoSkip: true,
                                    maxTicksLimit: 12,
                                    callback: function (val) { return fmtTick(val); },
                                },
                            },
                            y: {
                                grace: '5%',
                                grid: { color: gridColor },
                                ticks: {
                                    color: tickColor,
                                    font: { size: 11 },
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        }
                    }
                });

                resetBtn.addEventListener('click', function () {
                    chart.resetZoom();
                    resetBtn.style.display = 'none';
                });
            })();
        </script>
    @else
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm px-6 py-12 text-center text-gray-500 dark:text-gray-400">
            Geen ratinghistorie beschikbaar.
        </div>
    @endif
</div>
@endsection

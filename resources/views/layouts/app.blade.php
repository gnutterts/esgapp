<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Apply theme before first paint to prevent flash --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme') ?? 'system';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (t === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-200">

    {{-- Skip to content (accessibility) --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2 focus:bg-navy focus:text-white focus:rounded focus:text-sm focus:font-medium">
        Ga naar inhoud
    </a>

    {{-- Navigation --}}
    <nav class="bg-navy dark:bg-navy-dark shadow-md" aria-label="Hoofdnavigatie">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Left: Brand + primary links --}}
                <div class="flex items-center gap-6">
                    <a href="/"
                       class="text-white font-bold text-lg tracking-wide hover:text-blue-200 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy rounded"
                       aria-label="ESGapp – naar startpagina">
                        ESGapp
                    </a>

                    {{-- Desktop links --}}
                    <div class="hidden md:flex items-center gap-1" role="list">
                        <a href="/stand" role="listitem"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('stand*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('stand*')) aria-current="page" @endif>
                            Stand
                        </a>
                        <a href="{{ route('indeling.latest') }}" role="listitem"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('indeling*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('indeling*')) aria-current="page" @endif>
                            Indeling
                        </a>
                        <a href="{{ route('uitslag.latest') }}" role="listitem"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('uitslag*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('uitslag*')) aria-current="page" @endif>
                            Uitslagen
                         </a>
                         <a href="{{ route('ratings') }}" role="listitem"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('ratings*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('ratings*')) aria-current="page" @endif>
                            Ratings
                        </a>
                        <a href="{{ route('documentatie.index') }}" role="listitem"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('documentatie*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('documentatie*')) aria-current="page" @endif>
                            Documentatie
                        </a>
                        @auth
                            <a href="/dashboard" role="listitem"
                               class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('dashboard*') ? 'bg-navy-light text-white' : '' }}"
                               @if(request()->is('dashboard*')) aria-current="page" @endif>
                                Dashboard
                            </a>
                            @if(auth()->user()->role === 'wedstrijdleider')
                                <a href="/beheer" role="listitem"
                                   class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy {{ request()->is('beheer*') ? 'bg-navy-light text-white' : '' }}"
                                   @if(request()->is('beheer*')) aria-current="page" @endif>
                                    Beheer
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                {{-- Right: Theme toggle + Auth --}}
                <div class="hidden md:flex items-center gap-3">

                    {{-- Theme toggle --}}
                    <div class="flex items-center bg-navy-light rounded-full p-0.5 gap-0.5" role="group" aria-label="Kleurmodus kiezen">
                        {{-- Light --}}
                        <button type="button" data-theme-btn="light"
                                class="rounded-full p-1.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                aria-label="Lichte modus" aria-pressed="false" title="Licht">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364-.707.707M6.343 17.657l-.707.707m12.728 0-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/>
                            </svg>
                        </button>
                        {{-- System --}}
                        <button type="button" data-theme-btn="system"
                                class="rounded-full p-1.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                aria-label="Systeemvoorkeur" aria-pressed="false" title="Systeem">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-4m-4 4v-4m0 0H8m4 0h4"/>
                            </svg>
                        </button>
                        {{-- Dark --}}
                        <button type="button" data-theme-btn="dark"
                                class="rounded-full p-1.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                aria-label="Donkere modus" aria-pressed="false" title="Donker">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </button>
                    </div>

                    @auth
                        <span class="text-blue-200 text-sm" aria-hidden="true">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy">
                                Uitloggen
                            </button>
                        </form>
                    @else
                        <a href="/login"
                           class="text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-navy">
                            Inloggen
                        </a>
                    @endauth
                </div>

                {{-- Mobile right: theme + hamburger --}}
                <div class="md:hidden flex items-center gap-2">
                    {{-- Compact dark-mode toggle for mobile --}}
                    <button type="button" id="mobile-theme-toggle"
                            class="text-blue-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white p-2 rounded"
                            aria-label="Kleurmodus wisselen">
                        <svg id="mobile-theme-icon-light" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364-.707.707M6.343 17.657l-.707.707m12.728 0-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/>
                        </svg>
                        <svg id="mobile-theme-icon-dark" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <svg id="mobile-theme-icon-system" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-4m-4 4v-4m0 0H8m4 0h4"/>
                        </svg>
                    </button>

                    {{-- Hamburger --}}
                    <button id="mobile-menu-toggle"
                            type="button"
                            class="text-blue-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white p-2 rounded"
                            aria-label="Menu openen"
                            aria-expanded="false"
                            aria-controls="mobile-menu">
                        <svg id="icon-open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg id="icon-close" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-navy-light" role="navigation" aria-label="Mobiele navigatie">
            <div class="px-4 pt-2 pb-3 space-y-1">
                <a href="/stand"
                   class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('stand*') ? 'bg-navy-light text-white' : '' }}"
                   @if(request()->is('stand*')) aria-current="page" @endif>Stand</a>
                <a href="{{ route('indeling.latest') }}"
                   class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('indeling*') ? 'bg-navy-light text-white' : '' }}"
                   @if(request()->is('indeling*')) aria-current="page" @endif>Indeling</a>
                <a href="{{ route('uitslag.latest') }}"
                   class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('uitslag*') ? 'bg-navy-light text-white' : '' }}"
                   @if(request()->is('uitslag*')) aria-current="page" @endif>Uitslagen</a>
                <a href="{{ route('ratings') }}"
                   class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('ratings*') ? 'bg-navy-light text-white' : '' }}"
                   @if(request()->is('ratings*')) aria-current="page" @endif>Ratings</a>
                <a href="{{ route('documentatie.index') }}"
                   class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('documentatie*') ? 'bg-navy-light text-white' : '' }}"
                   @if(request()->is('documentatie*')) aria-current="page" @endif>Documentatie</a>
                @auth
                    <a href="/dashboard"
                       class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('dashboard*') ? 'bg-navy-light text-white' : '' }}"
                       @if(request()->is('dashboard*')) aria-current="page" @endif>Dashboard</a>
                    @if(auth()->user()->role === 'wedstrijdleider')
                        <a href="/beheer"
                           class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors {{ request()->is('beheer*') ? 'bg-navy-light text-white' : '' }}"
                           @if(request()->is('beheer*')) aria-current="page" @endif>Beheer</a>
                    @endif
                    <div class="border-t border-navy-light mt-2 pt-2">
                        <span class="block text-blue-300 text-xs px-3 py-1">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors cursor-pointer">
                                Uitloggen
                            </button>
                        </form>
                    </div>
                @else
                    <div class="border-t border-navy-light mt-2 pt-2">
                        <a href="/login"
                           class="block text-blue-100 hover:text-white hover:bg-navy-light px-3 py-2 rounded text-sm font-medium transition-colors">
                            Inloggen
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-950/40 border-l-4 border-green-500 text-green-800 dark:text-green-200 px-4 py-3 mx-4 mt-4 rounded shadow-sm flex items-start justify-between"
             role="alert" aria-live="polite">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"/>
                </svg>
                <span class="text-sm">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700 ml-4 flex-shrink-0" aria-label="Melding sluiten">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                          clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-950/40 border-l-4 border-red-500 text-red-800 dark:text-red-200 px-4 py-3 mx-4 mt-4 rounded shadow-sm flex items-start justify-between"
             role="alert" aria-live="assertive">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1-5a1 1 0 100 2 1 1 0 000-2z"
                          clip-rule="evenodd"/>
                </svg>
                <span class="text-sm">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 ml-4 flex-shrink-0" aria-label="Melding sluiten">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                          clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 dark:bg-blue-950/40 border-l-4 border-blue-500 text-blue-800 dark:text-blue-200 px-4 py-3 mx-4 mt-4 rounded shadow-sm flex items-start justify-between"
             role="alert" aria-live="polite">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1-5a1 1 0 100 2 1 1 0 000-2z"
                          clip-rule="evenodd"/>
                </svg>
                <span class="text-sm">{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700 ml-4 flex-shrink-0" aria-label="Melding sluiten">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                          clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Main content --}}
    <main id="main-content" class="flex-grow" tabindex="-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    @php
        $footerEmail      = \App\Models\Setting::get('footer_contact_email', 'wedstrijdleider@esgapp.nl');
        $footerToonContact = \App\Models\Setting::get('footer_toon_contact', '1') === '1';
        $footerToonKredit   = \App\Models\Setting::get('footer_toon_kredit', '1') === '1';
        $footerKreditPrefix = \App\Models\Setting::get('footer_kredit_prefix', 'Mogelijk gemaakt door');
        $footerKreditUrl    = \App\Models\Setting::get('footer_kredit_url', 'https://interioshops.nl');
    @endphp
    <footer class="bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
                <span>Emmer Schaak Genootschap &copy; {{ date('Y') }}</span>
                @if($footerToonContact && $footerEmail)
                    <a href="mailto:{{ $footerEmail }}" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">{{ $footerEmail }}</a>
                @endif
                @if($footerToonKredit)
                    <span>
                        {{ $footerKreditPrefix }}
                        @if($footerKreditUrl)
                            <a href="{{ $footerKreditUrl }}" target="_blank" rel="noopener" class="hover:opacity-80 transition-opacity" style="font-weight:700;letter-spacing:-0.02em;color:inherit;">Interio<span style="font-weight:400;color:#6c757d;"> Shops</span></a>
                        @else
                            <span style="font-weight:700;letter-spacing:-0.02em;">Interio<span style="font-weight:400;color:#6c757d;"> Shops</span></span>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script>
        // Mobile menu toggle
        (function () {
            var toggle = document.getElementById('mobile-menu-toggle');
            var menu = document.getElementById('mobile-menu');
            var iconOpen = document.getElementById('icon-open');
            var iconClose = document.getElementById('icon-close');

            toggle.addEventListener('click', function () {
                var isHidden = menu.classList.contains('hidden');
                menu.classList.toggle('hidden', !isHidden);
                iconOpen.classList.toggle('hidden', isHidden);
                iconClose.classList.toggle('hidden', !isHidden);
                toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                toggle.setAttribute('aria-label', isHidden ? 'Menu sluiten' : 'Menu openen');
            });
        })();

        // Mobile theme cycle: system → light → dark → system
        (function () {
            var btn = document.getElementById('mobile-theme-toggle');
            if (!btn) return;
            var icons = {
                light: document.getElementById('mobile-theme-icon-light'),
                dark: document.getElementById('mobile-theme-icon-dark'),
                system: document.getElementById('mobile-theme-icon-system'),
            };
            var order = ['system', 'light', 'dark'];

            function showIcon(t) {
                Object.keys(icons).forEach(function (k) {
                    icons[k].classList.toggle('hidden', k !== t);
                });
            }

            function getTheme() { return localStorage.getItem('theme') ?? 'system'; }

            function applyTheme(t) {
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var useDark = t === 'dark' || (t === 'system' && prefersDark);
                document.documentElement.classList.toggle('dark', useDark);
            }

            showIcon(getTheme());

            btn.addEventListener('click', function () {
                var current = getTheme();
                var next = order[(order.indexOf(current) + 1) % order.length];
                localStorage.setItem('theme', next);
                applyTheme(next);
                showIcon(next);
            });
        })();
    </script>

    @stack('scripts')

</body>
</html>

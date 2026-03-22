@extends('layouts.app')

@section('title', 'Beheer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row gap-8">

        {{-- Sidebar navigation --}}
        <aside class="md:w-56 flex-shrink-0" aria-label="Beheermenu">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-navy dark:bg-navy-dark px-4 py-3">
                    <h2 class="text-white font-semibold text-sm uppercase tracking-wide">Beheer</h2>
                </div>
                <nav class="flex flex-col">
                    <a href="/beheer/rondes"
                       class="flex items-center py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-navy/20 hover:text-navy dark:hover:text-blue-300 border-b border-gray-100 dark:border-gray-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy {{ request()->is('beheer/rondes*') ? 'bg-blue-50 dark:bg-navy/30 text-navy dark:text-blue-300 font-medium border-l-4 border-l-navy pl-3 pr-4' : 'px-4' }}"
                       @if(request()->is('beheer/rondes*')) aria-current="page" @endif>
                        <svg class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Rondes
                    </a>
                    <a href="/beheer/spelers"
                       class="flex items-center py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-navy/20 hover:text-navy dark:hover:text-blue-300 border-b border-gray-100 dark:border-gray-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy {{ request()->is('beheer/spelers*') ? 'bg-blue-50 dark:bg-navy/30 text-navy dark:text-blue-300 font-medium border-l-4 border-l-navy pl-3 pr-4' : 'px-4' }}"
                       @if(request()->is('beheer/spelers*')) aria-current="page" @endif>
                        <svg class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Spelers
                    </a>
                    <a href="/beheer/seizoenen"
                       class="flex items-center py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-navy/20 hover:text-navy dark:hover:text-blue-300 border-b border-gray-100 dark:border-gray-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy {{ request()->is('beheer/seizoenen*') ? 'bg-blue-50 dark:bg-navy/30 text-navy dark:text-blue-300 font-medium border-l-4 border-l-navy pl-3 pr-4' : 'px-4' }}"
                       @if(request()->is('beheer/seizoenen*')) aria-current="page" @endif>
                        <svg class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Seizoenen
                    </a>
                    <a href="/beheer/instellingen"
                       class="flex items-center py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-navy/20 hover:text-navy dark:hover:text-blue-300 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-navy {{ request()->is('beheer/instellingen*') ? 'bg-blue-50 dark:bg-navy/30 text-navy dark:text-blue-300 font-medium border-l-4 border-l-navy pl-3 pr-4' : 'px-4' }}"
                       @if(request()->is('beheer/instellingen*')) aria-current="page" @endif>
                        <svg class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Instellingen
                    </a>
                </nav>
            </div>
        </aside>

        {{-- Main beheer content --}}
        <div class="flex-grow min-w-0">
            @yield('beheer-content')
        </div>

    </div>
</div>
@endsection

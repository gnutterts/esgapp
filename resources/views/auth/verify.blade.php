@extends('layouts.app')

@section('title', 'Code invoeren')

@section('content')
<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md overflow-hidden">

            {{-- Card header --}}
            <div class="bg-navy px-8 py-6 text-center">
                <h1 class="text-white text-2xl font-bold">ESGapp</h1>
                <p class="text-blue-200 text-sm mt-1">Inloggen via e-mail</p>
            </div>

            {{-- Card body --}}
            <div class="px-8 py-8">

                {{-- Success icon --}}
                <div class="flex justify-center mb-5" aria-hidden="true">
                    <div class="bg-green-100 dark:bg-green-900/30 rounded-full p-4">
                        <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <h2 class="text-gray-800 dark:text-gray-100 text-xl font-semibold mb-3 text-center">Controleer je e-mail</h2>

                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed text-center mb-6">
                    Er is een inlogcode verstuurd naar je e-mailadres.
                    Voer de <span class="font-medium text-gray-800 dark:text-gray-200">6-cijferige code</span> hieronder in.
                </p>

                <form method="POST" action="{{ route('login.authenticate') }}" novalidate>
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Inlogcode
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                            autofocus
                            placeholder="000000"
                            class="w-full px-4 py-3 border rounded-lg text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-700 text-center text-2xl font-mono tracking-[0.5em] placeholder-gray-300 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent transition
                                   {{ $errors->has('code') ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}"
                            aria-describedby="{{ $errors->has('code') ? 'code-error' : 'code-hint' }}"
                            aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                        >
                        @error('code')
                            <p id="code-error" class="mt-1.5 text-red-600 dark:text-red-400 text-xs" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-navy hover:bg-navy-light text-white font-semibold py-2.5 px-4 rounded-lg transition-colors shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-navy dark:focus-visible:ring-offset-gray-800">
                        Inloggen
                    </button>
                </form>

                <p id="code-hint" class="text-gray-500 dark:text-gray-400 text-xs mt-4 text-center">
                    De code is <span class="font-medium text-gray-700 dark:text-gray-300">15 minuten</span> geldig.
                    Geen e-mail ontvangen? Controleer je spammap of
                    <a href="/login" class="text-navy dark:text-blue-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">probeer het opnieuw</a>.
                </p>

            </div>

        </div>

    </div>
</div>
@endsection

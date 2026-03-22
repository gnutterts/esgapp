@extends('layouts.app')

@section('title', 'Inloggen')

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
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-6 text-center">
                    Vul je e-mailadres in. Je ontvangt een inlogcode waarmee je kunt inloggen — geen wachtwoord nodig.
                </p>

                <form method="POST" action="/login" novalidate>
                    @csrf

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            E-mailadres
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="jouw@email.nl"
                            class="w-full px-4 py-2.5 border rounded-lg text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-700 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent transition
                                   {{ $errors->has('email') ? 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600' }}"
                            aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        >
                        @error('email')
                            <p id="email-error" class="mt-1.5 text-red-600 dark:text-red-400 text-xs" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-navy hover:bg-navy-light text-white font-semibold py-2.5 px-4 rounded-lg transition-colors shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-navy dark:focus-visible:ring-offset-gray-800">
                        Stuur inlogcode
                    </button>
                </form>
            </div>

        </div>

        <p class="text-center text-gray-400 dark:text-gray-500 text-xs mt-6">
            Alleen geregistreerde spelers kunnen inloggen.
        </p>

    </div>
</div>
@endsection

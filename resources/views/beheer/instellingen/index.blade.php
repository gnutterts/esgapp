@extends('layouts.beheer')

@section('title', 'Instellingen — Beheer')

@section('beheer-content')

<x-back-link href="{{ route('beheer') }}">Terug naar beheer</x-back-link>

<div class="mt-4">
    <x-card title="Instellingen">
        <form method="POST" action="{{ route('beheer.instellingen.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

                {{-- Footer e-mailadres --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                        Footer
                    </h3>

                    <div class="space-y-4">

                        {{-- Toon contactregel --}}
                        <div class="flex items-start gap-3">
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="checkbox"
                                       id="footer_toon_contact"
                                       name="footer_toon_contact"
                                       value="1"
                                       {{ $toonFooter === '1' ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy focus:ring-navy dark:focus:ring-blue-400 bg-white dark:bg-gray-700 cursor-pointer">
                            </div>
                            <div>
                                <label for="footer_toon_contact" class="text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                                    Toon contactregel in footer
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Als dit uitstaat, wordt het e-mailadres niet getoond in de footer.
                                </p>
                            </div>
                        </div>

                        {{-- E-mailadres --}}
                        <div>
                            <label for="footer_contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                E-mailadres wedstrijdleider
                            </label>
                            <input type="email"
                                   id="footer_contact_email"
                                   name="footer_contact_email"
                                   value="{{ old('footer_contact_email', $contactEmail) }}"
                                   placeholder="wedstrijdleider@esgapp.nl"
                                   class="w-full sm:w-96 border rounded-lg px-3 py-2 text-sm
                                          text-gray-900 dark:text-gray-100
                                          bg-white dark:bg-gray-700
                                          border-gray-300 dark:border-gray-600
                                          placeholder-gray-400 dark:placeholder-gray-500
                                          focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent
                                          transition
                                          @error('footer_contact_email') border-red-400 dark:border-red-500 @enderror">
                            @error('footer_contact_email')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Dit adres wordt getoond als klikbare mailto-link in de footer.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Kredietvermelding --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                        Kredietvermelding
                    </h3>

                    <div class="space-y-4">

                        {{-- Toon kredietvermelding --}}
                        <div class="flex items-start gap-3">
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="checkbox"
                                       id="footer_toon_kredit"
                                       name="footer_toon_kredit"
                                       value="1"
                                       {{ $toonKredit === '1' ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-navy focus:ring-navy dark:focus:ring-blue-400 bg-white dark:bg-gray-700 cursor-pointer">
                            </div>
                            <div>
                                <label for="footer_toon_kredit" class="text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                                    Toon kredietvermelding in footer
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    De "Mogelijk gemaakt door …" regel rechts in de footer.
                                </p>
                            </div>
                        </div>

                        {{-- Tekst voor logo --}}
                        <div>
                            <label for="footer_kredit_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Tekst voor logo
                            </label>
                            <input type="text"
                                   id="footer_kredit_prefix"
                                   name="footer_kredit_prefix"
                                   value="{{ old('footer_kredit_prefix', $kreditPrefix) }}"
                                   placeholder="Mogelijk gemaakt door"
                                   maxlength="100"
                                   class="w-full sm:w-96 border rounded-lg px-3 py-2 text-sm
                                          text-gray-900 dark:text-gray-100
                                          bg-white dark:bg-gray-700
                                          border-gray-300 dark:border-gray-600
                                          placeholder-gray-400 dark:placeholder-gray-500
                                          focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent
                                          transition
                                          @error('footer_kredit_prefix') border-red-400 dark:border-red-500 @enderror">
                            @error('footer_kredit_prefix')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Voorbeeld: <span class="text-gray-700 dark:text-gray-300">{{ old('footer_kredit_prefix', $kreditPrefix) ?: 'Mogelijk gemaakt door' }}</span> <span style="font-weight:700;letter-spacing:-0.02em;">Interio<span style="font-weight:400;color:#6c757d;"> Shops</span></span>
                            </p>
                        </div>

                        {{-- URL --}}
                        <div>
                            <label for="footer_kredit_url" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                URL <span class="font-normal text-gray-400 dark:text-gray-500">(optioneel)</span>
                            </label>
                            <input type="url"
                                   id="footer_kredit_url"
                                   name="footer_kredit_url"
                                   value="{{ old('footer_kredit_url', $kreditUrl) }}"
                                   placeholder="https://interioshops.nl"
                                   class="w-full sm:w-96 border rounded-lg px-3 py-2 text-sm
                                          text-gray-900 dark:text-gray-100
                                          bg-white dark:bg-gray-700
                                          border-gray-300 dark:border-gray-600
                                          placeholder-gray-400 dark:placeholder-gray-500
                                          focus:outline-none focus:ring-2 focus:ring-navy dark:focus:ring-blue-400 focus:border-transparent
                                          transition
                                          @error('footer_kredit_url') border-red-400 dark:border-red-500 @enderror">
                            @error('footer_kredit_url')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Leeg laten om de naam zonder link te tonen.
                            </p>
                        </div>

                    </div>
                </div>

            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex gap-3">
                <x-button type="submit" variant="primary">Opslaan</x-button>
                <x-button href="{{ route('beheer') }}" variant="secondary">Annuleren</x-button>
            </div>

        </form>
    </x-card>
</div>

@endsection

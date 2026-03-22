{{-- Variables: $user --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 space-y-4">
    {{-- Auto-deelname --}}
    <x-toggle
        name="auto_participate"
        :value="$user->auto_participate"
        action="{{ route('auto-participate.toggle') }}"
        label="Auto-deelname"
        description="Wanneer ingeschakeld ben je automatisch beschikbaar voor alle rondes waar inschrijving nog open is. Je kunt je per ronde alsnog afmelden."
    />

    <hr class="border-gray-100 dark:border-gray-700">

    {{-- KNSB-rating zichtbaarheid --}}
    <x-toggle
        name="show_knsb_rating"
        :value="$user->show_knsb_rating"
        action="{{ route('rating.toggle-show', 'knsb') }}"
        label="KNSB-rating"
        description="Toon je KNSB-rating (ELO {{ $user->elo_rating ?? 1200 }}) op de publieke ratingpagina.{{ $user->knsb_relatienummer ? ' KNSB-nr: '.$user->knsb_relatienummer.'.' : '' }}"
    />
</div>

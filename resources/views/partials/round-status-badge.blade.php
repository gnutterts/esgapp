{{-- Round status badge --}}
{{-- Required: $status (string: scheduled, registration_closed, paired, completed) --}}
@php
    $roundStatusMap = [
        'scheduled'            => ['label' => 'Gepland',                'class' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200'],
        'registration_closed'  => ['label' => 'Inschrijving gesloten', 'class' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-200'],
        'paired'               => ['label' => 'Ingedeeld',              'class' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200'],
        'completed'            => ['label' => 'Afgerond',               'class' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200'],
    ];
    $roundStatusInfo = $roundStatusMap[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'];
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roundStatusInfo['class'] }}">
    {{ $roundStatusInfo['label'] }}
</span>

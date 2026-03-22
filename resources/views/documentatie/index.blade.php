@extends('layouts.app')

@section('title', 'Documentatie')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-navy dark:text-white">Documentatie</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Informatie over de competitie en de applicatie.</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($paginas as $slug => $pagina)
            <a href="{{ route('documentatie.show', $slug) }}"
               class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 hover:shadow-md hover:border-navy dark:hover:border-blue-500 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded-lg">
                <h2 class="text-lg font-semibold text-navy dark:text-blue-400 mb-2">{{ $pagina['titel'] }}</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $pagina['beschrijving'] }}</p>
            </a>
        @endforeach
    </div>

</div>
@endsection

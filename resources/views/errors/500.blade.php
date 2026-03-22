@extends('layouts.app')

@section('title', 'Serverfout')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-20 text-center">
    <h1 class="text-6xl font-bold text-navy dark:text-white mb-4">500</h1>
    <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">Er is iets misgegaan. Probeer het later opnieuw.</p>
    <a href="{{ route('home') }}"
       class="inline-flex items-center px-5 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-dark transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-navy dark:focus-visible:ring-offset-gray-900">
        Terug naar de homepage
    </a>
</div>
@endsection

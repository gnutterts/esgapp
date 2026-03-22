@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-navy dark:text-blue-300">Welkom, {{ $user->name }}</h1>
        @if($season)
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Seizoen: {{ $season->name }}</p>
        @endif
    </div>

    @include('partials.dashboard.period-info')
    @include('partials.dashboard.standing-card')
    @include('partials.dashboard.toggles')
    @include('partials.dashboard.upcoming-rounds')
    @include('partials.dashboard.recent-rounds')

</div>
@endsection

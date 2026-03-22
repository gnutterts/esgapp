@extends('layouts.app')

@section('title', $titel)

@section('content')
<div id="top" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-6">
        <a href="{{ route('documentatie.index') }}"
           class="text-navy dark:text-blue-400 hover:underline text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 rounded">&larr; Terug naar overzicht</a>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 sm:p-8">
        <div class="prose dark:prose-invert max-w-none">
            {!! $html !!}
        </div>
    </div>

</div>

{{-- Back to top button --}}
<a href="#top"
   id="back-to-top"
   class="fixed bottom-6 right-6 bg-navy dark:bg-blue-600 text-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center opacity-0 pointer-events-none transition-opacity hover:bg-navy-light dark:hover:bg-blue-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2"
   aria-label="Naar boven"
   title="Naar boven">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
    </svg>
</a>

<script>
    const btn = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btn.classList.remove('opacity-0', 'pointer-events-none');
            btn.classList.add('opacity-100');
        } else {
            btn.classList.add('opacity-0', 'pointer-events-none');
            btn.classList.remove('opacity-100');
        }
    });
</script>
@endsection

@props([
    'name'    => '',     // form input name
    'value'   => false,  // current boolean state
    'on'      => 'Aan',
    'off'     => 'Uit',
    'action'  => '',     // form action URL
    'method'  => 'POST',
    'label'   => null,
    'description' => null,
])

<form method="POST" action="{{ $action }}">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="flex items-center justify-between gap-4">
        @if($label || $description)
            <div>
                @if($label)
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</p>
                @endif
                @if($description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $description }}</p>
                @endif
            </div>
        @endif

        <input type="hidden" name="{{ $name }}" value="{{ $value ? '0' : '1' }}">
        <button type="submit"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 {{ $value ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                role="switch"
                aria-checked="{{ $value ? 'true' : 'false' }}"
                @if($label) aria-label="{{ $label }}" @endif>
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $value ? 'translate-x-5' : 'translate-x-0' }}"></span>
        </button>
    </div>
</form>

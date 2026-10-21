@props(['icon' => null])

{{-- Destructive action — always solid red, never an outline. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-danger-600 px-4 text-sm font-semibold text-white shadow-soft transition-colors duration-150 hover:bg-danger-700 focus-visible:outline-danger-500 disabled:cursor-not-allowed disabled:opacity-55']) }}>
    @if ($icon)<x-icon :name="$icon" :size="17" />@endif
    {{ $slot }}
</button>

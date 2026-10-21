@props(['icon' => null])

{{-- Solid brand action — the standard submit control of the forms. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-brand-800 px-4 text-sm font-bold text-white transition-colors duration-150 hover:bg-brand-900 focus-visible:outline-brand-700 disabled:cursor-not-allowed disabled:opacity-55']) }}>
    @if ($icon)<x-icon :name="$icon" :size="17" />@endif
    {{ $slot }}
</button>

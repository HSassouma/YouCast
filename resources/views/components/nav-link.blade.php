@props(['active', 'icon' => null])

@php
    $classes = ($active ?? false)
        ? 'inline-flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700'
        : 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-current' => ($active ?? false) ? 'page' : null]) }}>
    @if ($icon)<x-icon :name="$icon" :size="17" />@endif
    {{ $slot }}
</a>

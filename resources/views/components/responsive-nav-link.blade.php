@props(['active', 'icon' => null])

@php
    $classes = ($active ?? false)
        ? 'flex items-center gap-3 rounded-lg bg-brand-50 px-3 py-2.5 text-sm font-semibold text-brand-700'
        : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-current' => ($active ?? false) ? 'page' : null]) }}>
    @if ($icon)<x-icon :name="$icon" :size="18" />@endif
    {{ $slot }}
</a>

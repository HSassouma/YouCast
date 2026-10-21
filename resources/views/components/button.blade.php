@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null])

@php
    /**
     * The one button component of the design system.
     *
     * Sizes (documented in the report):
     *   sm → 36 px  dense tables and toolbars
     *   md → 40 px  page headers, navigation, lifecycle actions
     *   lg → 44 px  forms and filters (matches the 44 px inputs)
     *   xl → 48 px  public-site hero and appeal bands
     *
     * Placeholder buttons default to `type="submit"` like every other
     * submit control of the app; pass `type="button"` when needed.
     */
    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-bold transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-55';

    $sizes = [
        'sm' => 'min-h-9 px-3 text-sm',
        'md' => 'min-h-10 px-3.5 text-sm',
        'lg' => 'min-h-11 px-4 text-sm',
        'xl' => 'min-h-12 px-6 text-base',
    ];

    $variants = [
        'primary' => 'bg-brand-800 text-white hover:bg-brand-900 focus-visible:outline-brand-700',
        'secondary' => 'border border-slate-300 bg-white text-slate-800 hover:border-brand-300 hover:bg-brand-50',
        'quiet' => 'text-brand-700 hover:bg-brand-50',
        'danger' => 'bg-danger-600 text-white shadow-soft hover:bg-danger-700 focus-visible:outline-danger-500',
        'danger-soft' => 'border border-danger-200 bg-danger-50 text-danger-700 hover:bg-danger-100',
        'success' => 'bg-success-600 text-white shadow-soft hover:bg-success-700',
        'dark' => 'bg-slate-900 text-white hover:bg-slate-800',
    ];

    $classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']));
    $iconSize = $size === 'xl' ? 18 : 17;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
    </a>
@else
    <button type="submit" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
    </button>
@endif

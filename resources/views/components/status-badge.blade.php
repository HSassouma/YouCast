@props(['status', 'dot' => true, 'icon' => null])

@php
    $label = is_object($status) && method_exists($status, 'label') ? $status->label() : (string) $status;
    $value = is_object($status) && isset($status->value) ? $status->value : (string) $status;

    // The label always carries the state, the colour only reinforces it.
    $classes = match ($value) {
        'published', 'accepted', 'registered', 'open' => 'bg-success-50 text-success-800 ring-success-200',
        'draft', 'pending' => 'bg-warning-50 text-warning-800 ring-warning-200',
        'closed' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'cancelled', 'rejected', 'archived' => 'bg-danger-50 text-danger-800 ring-danger-200',
        default => 'bg-brand-50 text-brand-700 ring-brand-200',
    };

    $dotClasses = match ($value) {
        'published', 'accepted', 'registered', 'open' => 'bg-success-600',
        'draft', 'pending' => 'bg-warning-600',
        'closed' => 'bg-slate-400',
        'cancelled', 'rejected', 'archived' => 'bg-danger-600',
        default => 'bg-brand-500',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$classes]) }}>
    @if ($icon)
        <x-icon :name="$icon" :size="14" />
    @elseif ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}" aria-hidden="true"></span>
    @endif
    {{ $label }}
</span>

@props(['title', 'icon' => 'sparkles', 'actionHref' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center']) }}>
    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
        <x-icon :name="$icon" :size="22" />
    </span>
    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-prose text-sm leading-relaxed text-slate-600">{{ $slot }}</p>
    @if ($actionHref && $actionLabel)
        <span class="mt-6 block">
            <x-button :href="$actionHref">{{ $actionLabel }}</x-button>
        </span>
    @endif
</div>

@props(['title', 'subtitle' => null, 'backHref' => null, 'backLabel' => null])

{{--
    The single page-header pattern of the application: an optional back
    link, the title (with an optional badge beside it), a one-line subtitle
    and an actions group pinned to the footer of the row.

    <x-page-header title="Mes idées" subtitle="…" backHref="…" backLabel="…">
        <x-slot name="badge"><x-status-badge …/></x-slot>
        <x-slot name="actions">…buttons…</x-slot>
    </x-page-header>

    Pass a `heading` slot to compose the title area by hand (eyebrow above,
    custom elements beside the H1) while keeping the same spacing rules.
--}}

<div class="yc-page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($backHref)
            <a href="{{ $backHref }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition-colors duration-150 hover:text-brand-700">
                <x-icon name="arrow-left" :size="16" />
                {{ $backLabel ?? 'Retour' }}
            </a>
        @endif

        <div class="flex flex-wrap items-center gap-2.5 {{ $backHref ? 'mt-2' : '' }}">
            @isset($heading)
                {{ $heading }}
            @else
                <h1 class="yc-page-heading yc-break-anywhere text-3xl font-bold leading-tight tracking-tight text-slate-900">{{ $title }}</h1>
                @isset($badge)
                    {{ $badge }}
                @endisset
            @endisset
        </div>

        @if ($subtitle)
            <p class="yc-break-anywhere mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2.5">
            {{ $actions }}
        </div>
    @endisset
</div>

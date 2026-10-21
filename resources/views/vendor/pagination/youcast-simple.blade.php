@if ($paginator->hasMorePages())
    <nav role="navigation" aria-label="Pagination">
        <div class="flex items-center justify-between gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-400 opacity-60">
                    <x-icon name="arrow-left" :size="16" />
                    Précédent
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 hover:text-brand-700">
                    <x-icon name="arrow-left" :size="16" />
                    Précédent
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 hover:text-brand-700">
                    Suivant
                    <x-icon name="arrow-right" :size="16" />
                </a>
            @endif
        </div>
    </nav>
@endif

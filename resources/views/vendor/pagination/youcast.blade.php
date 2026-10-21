{{--
    YouCast pagination. The framework default (grey/indigo, rounded-md)
    does not follow the design tokens, so the app ships its own view and
    registers it as the paginator default in AppServiceProvider.
--}}

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col items-center justify-between gap-3 sm:flex-row">

        {{-- Compact pager on mobile --}}
        <div class="flex w-full items-center justify-between gap-2 sm:hidden">
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
            @else
                <span class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-400 opacity-60">
                    Suivant
                    <x-icon name="arrow-right" :size="16" />
                </span>
            @endif
        </div>

        {{-- Full pager on ≥ sm --}}
        <div class="hidden w-full items-center justify-between gap-3 sm:flex">
            <p class="text-sm text-slate-500">
                Affichage de
                <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
                à
                <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
                sur
                <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
                {{ Str::plural('résultat', $paginator->total()) }}
            </p>

            <div class="flex items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 opacity-60" aria-hidden="true">
                        <x-icon name="arrow-left" :size="16" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 hover:text-brand-700">
                        <x-icon name="arrow-left" :size="16" />
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-slate-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-brand-600 px-2.5 text-sm font-semibold text-white shadow-soft">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Aller à la page {{ $page }}"
                                   class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 hover:text-brand-700">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors duration-150 hover:border-slate-400 hover:bg-slate-50 hover:text-brand-700">
                        <x-icon name="arrow-right" :size="16" />
                    </a>
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 opacity-60" aria-hidden="true">
                        <x-icon name="arrow-right" :size="16" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif

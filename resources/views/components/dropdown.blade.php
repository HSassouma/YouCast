@props(['align' => 'right', 'width' => '56', 'contentClasses' => ''])

@php
    $alignmentClasses = match ($align) {
        'left' => 'start-0',
        default => 'end-0',
    };

    $widthClasses = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        default => $width,
    };
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button type="button" @click="open = ! open" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start transition-colors duration-150 hover:bg-slate-100" :aria-expanded="open">
        {{ $trigger }}
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="absolute z-50 mt-2 {{ $widthClasses }} {{ $alignmentClasses }} overflow-hidden rounded-xl border border-slate-200 bg-white shadow-raised"
         style="display: none;"
         @click="open = false">
        <div class="py-1.5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>

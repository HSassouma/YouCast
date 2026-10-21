@props(['icon' => null])

<a {{ $attributes->merge(['class' => 'flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900']) }}>
    @if ($icon)<x-icon :name="$icon" :size="17" class="text-slate-400" />@endif
    {{ $slot }}
</a>

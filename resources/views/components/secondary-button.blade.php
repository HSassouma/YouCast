@props(['icon' => null])

{{-- Neutral outlined action — cancel, alternative option alongside a primary. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 transition-colors duration-150 hover:border-brand-300 hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-55']) }}>
    @if ($icon)<x-icon :name="$icon" :size="17" />@endif
    {{ $slot }}
</button>

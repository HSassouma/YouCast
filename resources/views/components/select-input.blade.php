@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-base text-slate-900 shadow-soft transition-colors duration-150 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500']) }}>
    {{ $slot }}
</select>

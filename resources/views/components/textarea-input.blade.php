@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'yc-form-control block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-base text-slate-900 shadow-soft transition-colors duration-200 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500']) }}></textarea>

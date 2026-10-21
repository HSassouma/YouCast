@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-slate-800']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-danger-600" aria-hidden="true">*</span>
        <span class="sr-only">(obligatoire)</span>
    @endif
</label>

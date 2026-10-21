@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-800']) }} role="status" aria-live="polite">
        <x-icon name="check-circle" :size="18" class="mt-0.5 text-success-600" />
        <p>{{ $status }}</p>
    </div>
@endif

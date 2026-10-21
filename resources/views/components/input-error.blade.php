@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm font-medium text-danger-700']) }} role="alert">
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5">
                <x-icon name="alert" :size="16" class="mt-0.5 text-danger-600" />
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif

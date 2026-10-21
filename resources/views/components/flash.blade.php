@if (session('status'))
    <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800">
        <x-icon name="check-circle" :size="18" class="mt-0.5 text-success-600" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-800">
        <x-icon name="alert" :size="18" class="mt-0.5 text-danger-600" />
        <div>
            <p class="font-semibold">Corrigez les points suivants :</p>
            <ul class="mt-1 list-disc space-y-1 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

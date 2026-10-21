<x-app-layout width="read">
    <x-slot name="title">Modifier {{ $activity->title }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Modifier l'activité"
            :subtitle="$activity->title.' · '.$activity->type?->label()"
            backHref="{{ route('admin.activities.show', $activity) }}"
            backLabel="Retour à l'activité" />
    </x-slot>

    <div>
        <x-flash />

        <form method="POST" action="{{ route('admin.activities.update', $activity) }}" class="panel p-6 sm:p-7">
            @csrf
            @method('PUT')

            @include('admin.activities._form', ['activity' => $activity, 'types' => $types])

            <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6">
                <x-primary-button icon="check-circle">Enregistrer les modifications</x-primary-button>
                <a href="{{ route('admin.activities.show', $activity) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>

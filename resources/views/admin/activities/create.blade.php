<x-app-layout width="read">
    <x-slot name="title">Nouvelle activité — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Nouvelle activité"
            subtitle="L'activité est créée en brouillon. Publiez-la quand tout est prêt."
            backHref="{{ route('admin.activities.index') }}"
            backLabel="Gestion des activités" />
    </x-slot>

    <div>
        <x-flash />

        <form method="POST" action="{{ route('admin.activities.store') }}" class="panel p-6 sm:p-7">
            @csrf

            @include('admin.activities._form', ['activity' => null, 'types' => $types])

            <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6">
                <x-primary-button icon="check-circle">Créer l'activité</x-primary-button>
                <a href="{{ route('admin.activities.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>

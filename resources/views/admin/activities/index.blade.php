<x-app-layout>
    <x-slot name="title">Gestion des activités — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Gestion des activités"
            subtitle="Créez, publiez et suivez les sessions proposées par le BDE.">
            <x-slot name="actions">
                <x-button href="{{ route('admin.activities.create') }}" icon="plus">Nouvelle activité</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        <form method="GET" class="panel mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:p-5">
            <div class="lg:col-span-2">
                <label for="q" class="block text-sm font-semibold text-slate-800">Rechercher</label>
                <x-text-input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Titre ou catégorie" class="mt-1.5" />
            </div>
            <div>
                <label for="status" class="block text-sm font-semibold text-slate-800">Statut</label>
                <x-select-input id="status" name="status" class="mt-1.5">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="flex items-end">
                <x-button size="lg">Filtrer</x-button>
            </div>
        </form>

        @if ($activities->isEmpty())
            <x-empty-state title="Aucune activité" icon="calendar" actionHref="{{ route('admin.activities.create') }}" actionLabel="Créer une activité">
                Les sessions publiées par le BDE apparaîtront ici.
            </x-empty-state>
        @else
            <div class="panel overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Titre</th>
                            <th scope="col">Type</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Inscriptions</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $activity->title }}</td>
                                <td class="text-slate-600">{{ $activity->type->label() }}</td>
                                <td><x-status-badge :status="$activity->status" /></td>
                                <td class="text-slate-600">
                                    {{ $activity->active_registrations_count }}{{ $activity->capacity ? ' / '.$activity->capacity : '' }}
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.activities.show', $activity) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline">
                                        Ouvrir
                                        <x-icon name="chevron-right" :size="15" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-8">{{ $activities->links() }}</div>
        @endif
    </div>
</x-app-layout>

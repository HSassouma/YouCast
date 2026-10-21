<x-app-layout>
    <x-slot name="title">Modération des idées — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Modération des idées"
            subtitle="Les propositions des étudiants. Une idée acceptée peut devenir une activité." />
    </x-slot>

    <div>
        <x-flash />

        <form method="GET" class="panel mb-6 flex flex-wrap items-center gap-3 p-4">
            <label for="status" class="text-sm font-semibold text-slate-800">Statut</label>
            <x-select-input id="status" name="status" class="w-auto min-w-44" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-select-input>
            <noscript>
                <x-secondary-button type="submit">Filtrer</x-secondary-button>
            </noscript>
        </form>

        @if ($ideas->isEmpty())
            <x-empty-state title="Aucune idée" icon="bulb">
                Aucune proposition ne correspond à ce filtre.
            </x-empty-state>
        @else
            <div class="panel overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Titre</th>
                            <th scope="col">Auteur</th>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Envoyée le</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ideas as $idea)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $idea->title }}</td>
                                <td class="text-slate-600">{{ $idea->author?->name ?? 'Compte supprimé' }}</td>
                                <td class="text-slate-600">{{ $idea->category ?: '—' }}</td>
                                <td><x-status-badge :status="$idea->status" /></td>
                                <td class="whitespace-nowrap text-slate-500">{{ $idea->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.ideas.show', $idea) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline">
                                        Examiner
                                        <x-icon name="chevron-right" :size="15" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-8">{{ $ideas->links() }}</div>
        @endif
    </div>
</x-app-layout>

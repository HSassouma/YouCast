<x-app-layout>
    <x-slot name="title">Boîte de réception — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Boîte de réception"
            subtitle="Conversations privées ouvertes par les étudiants." />
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

        @if ($conversations->isEmpty())
            <x-empty-state title="Aucune conversation" icon="inbox">
                Aucun étudiant n'a encore écrit au BDE.
            </x-empty-state>
        @else
            <div class="panel overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Objet</th>
                            <th scope="col">Étudiant</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Non lus</th>
                            <th scope="col">Dernière activité</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conversation)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $conversation->subject }}</td>
                                <td class="text-slate-600">{{ $conversation->student?->name ?? 'Compte supprimé' }}</td>
                                <td><x-status-badge :status="$conversation->status" /></td>
                                <td class="text-slate-600">
                                    @if ($conversation->unread_count > 0)
                                        <span class="inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">{{ $conversation->unread_count }}</span>
                                    @else
                                        <span class="text-slate-400">0</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-slate-500">{{ $conversation->last_message_at?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.messages.show', $conversation) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline">
                                        Ouvrir
                                        <x-icon name="chevron-right" :size="15" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-8">{{ $conversations->links() }}</div>
        @endif
    </div>
</x-app-layout>

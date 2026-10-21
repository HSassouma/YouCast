<x-app-layout width="wide">
    <x-slot name="title">Messages — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Messages"
            subtitle="Échanges privés avec le BDE. Seuls vous et l'administration pouvez les lire.">
            @can('create', App\Models\Conversation::class)
                <x-slot name="actions">
                    <x-button href="{{ route('messages.create') }}" icon="plus">Nouveau message</x-button>
                </x-slot>
            @endcan
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        @if ($conversations->isEmpty())
            <x-empty-state title="Aucune conversation" icon="message" actionHref="{{ route('messages.create') }}" actionLabel="Écrire au BDE">
                Une question sur une activité, une équipe ou un défi ? Écrivez-nous directement.
            </x-empty-state>
        @else
            <ul class="panel divide-y divide-slate-100 overflow-hidden">
                @foreach ($conversations as $conversation)
                    <li>
                        <a href="{{ route('messages.show', $conversation) }}" class="flex items-start justify-between gap-4 p-5 transition-colors duration-150 hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $conversation->subject }}</span>
                                    @if ($conversation->unread_count > 0)
                                        <span class="inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">
                                            {{ $conversation->unread_count }} nouveau{{ $conversation->unread_count > 1 ? 'x' : '' }}
                                        </span>
                                    @endif
                                </p>
                                <p class="mt-1 line-clamp-1 text-sm text-slate-600">
                                    {{ $conversation->lastMessage?->body ?? 'Aucun message pour le moment.' }}
                                </p>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Dernière activité {{ $conversation->last_message_at?->translatedFormat('d M Y à H:i') ?? '—' }}
                                </p>
                            </div>
                            <x-status-badge :status="$conversation->status" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $conversations->links() }}</div>
        @endif
    </div>
</x-app-layout>

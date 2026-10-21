<x-app-layout width="wide">
    <x-slot name="title">Mes idées — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Mes idées"
            subtitle="Proposez un défi au BDE. Les idées acceptées deviennent des activités.">
            <x-slot name="actions">
                <x-button href="{{ route('ideas.create') }}" icon="plus">Proposer une idée</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        @if ($ideas->isEmpty())
            <x-empty-state title="Aucune idée pour le moment" icon="bulb" actionHref="{{ route('ideas.create') }}" actionLabel="Proposer une idée">
                Décrivez un défi, un projet ou un atelier qui manquerait à votre école.
            </x-empty-state>
        @else
            <ul class="panel divide-y divide-slate-100 overflow-hidden">
                @foreach ($ideas as $idea)
                    <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $idea->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $idea->category ?: 'Sans catégorie' }} · envoyée le {{ $idea->created_at->format('d M Y') }}
                            </p>
                        </div>
                        <x-status-badge :status="$idea->status" />
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $ideas->links() }}</div>
        @endif
    </div>
</x-app-layout>

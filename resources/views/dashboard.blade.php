<x-app-layout>
    <x-slot name="title">{{ $isAdmin ? 'Tableau de bord BDE' : 'Mon tableau de bord' }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            :title="$isAdmin ? 'Tableau de bord BDE' : 'Bonjour '.auth()->user()->name"
            :subtitle="$isAdmin
                ? 'Suivez les activités publiées, les inscriptions et les idées en attente de modération.'
                : 'Retrouvez vos inscriptions, vos idées et les prochaines sessions ouvertes.'">
            @unless ($isAdmin)
                <x-slot name="actions">
                    <x-button href="{{ route('activities.index') }}" icon="calendar">Découvrir les activités</x-button>
                </x-slot>
            @endunless
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        @if ($isAdmin)
            {{-- Key figures, all computed from the database --}}
            <section aria-labelledby="stats-title">
                <h2 id="stats-title" class="sr-only">Chiffres clés</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach ([
                        ['calendar', 'Activités publiées', $stats['published'], 'publiées'],
                        ['flag', 'Brouillons', $stats['drafts'], 'à publier'],
                        ['users', 'Inscriptions', $stats['registrations'], 'actives'],
                        ['bulb', 'Idées à modérer', $stats['pending_ideas'], 'en attente'],
                        ['message', 'Messages non lus', $unreadMessages, 'non lus'],
                    ] as [$icon, $label, $value, $hint])
                        <div class="card p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-icon :name="$icon" :size="19" />
                                </span>
                                <p class="text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-900">{{ $label }}</p>
                            <p class="text-sm text-slate-500">{{ $hint }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <section class="panel">
                    <header class="panel-header">
                        <h2 class="text-base font-semibold text-slate-900">Dernières activités</h2>
                        <a href="{{ route('admin.activities.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Gérer</a>
                    </header>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($recentActivities as $activity)
                            <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.activities.show', $activity) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-brand-700">
                                        {{ $activity->title }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $activity->type->label() }}</p>
                                </div>
                                <x-status-badge :status="$activity->status" />
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-500">Aucune activité pour le moment.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="panel">
                    <header class="panel-header">
                        <h2 class="text-base font-semibold text-slate-900">Idées à modérer</h2>
                        <a href="{{ route('admin.ideas.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Tout voir</a>
                    </header>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($pendingIdeas as $idea)
                            <li class="px-5 py-3.5">
                                <a href="{{ route('admin.ideas.show', $idea) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-brand-700">
                                    {{ $idea->title }}
                                </a>
                                <p class="mt-0.5 text-xs text-slate-500">Proposée par {{ $idea->author?->name ?? 'Compte supprimé' }}</p>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-500">Aucune idée en attente.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-12">
                <div class="space-y-6 lg:col-span-8">
                    <section class="panel">
                        <header class="panel-header">
                            <h2 class="text-base font-semibold text-slate-900">Mes inscriptions</h2>
                            <a href="{{ route('activities.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Ajouter</a>
                        </header>
                        <ul class="divide-y divide-slate-100">
                            @forelse ($registrations as $registration)
                                <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                    <div class="min-w-0">
                                        <a href="{{ route('activities.show', $registration->activity) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-brand-700">
                                            {{ $registration->activity->title }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $registration->activity->type->label() }}</p>
                                    </div>
                                    <x-status-badge :status="$registration->status" />
                                </li>
                            @empty
                                <li class="px-5 py-10 text-center">
                                    <p class="text-sm font-medium text-slate-700">Vous n'avez pas encore d'inscription.</p>
                                    <p class="mt-1 text-sm text-slate-500">Parcourez le catalogue pour rejoindre une session.</p>
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-base font-semibold text-slate-900">Sessions ouvertes</h2>
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            @forelse ($openActivities as $activity)
                                <x-activity-card :activity="$activity" />
                            @empty
                                <span class="sm:col-span-2">
                                    <x-empty-state title="Aucune session ouverte" icon="calendar">
                                        Revenez bientôt : le BDE publie de nouvelles sessions régulièrement.
                                    </x-empty-state>
                                </span>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="space-y-6 lg:col-span-4">
                    <section class="panel">
                        <header class="panel-header">
                            <h2 class="text-base font-semibold text-slate-900">Mes idées</h2>
                            <a href="{{ route('ideas.create') }}" class="text-sm font-semibold text-brand-700 hover:underline">Proposer</a>
                        </header>
                        <ul class="divide-y divide-slate-100">
                            @forelse ($ideas as $idea)
                                <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                                    <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $idea->title }}</span>
                                    <x-status-badge :status="$idea->status" />
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-sm text-slate-500">
                                    Aucune idée envoyée. Proposez le prochain défi !
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="panel p-5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                            <x-icon name="message" :size="19" />
                        </span>
                        <h2 class="mt-3 text-base font-semibold text-slate-900">Une question ?</h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                            Écrivez directement au BDE, en privé.
                        </p>
                        <span class="mt-4 block">
                            <x-button href="{{ route('messages.create') }}" icon="plus">Écrire au BDE</x-button>
                        </span>
                    </section>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="title">{{ $activity->title }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            :title="$activity->title"
            :subtitle="$activity->type->label().' · créée par '.$activity->creator?->name"
            backHref="{{ route('admin.activities.index') }}"
            backLabel="Gestion des activités">
            <x-slot name="badge">
                <x-status-badge :status="$activity->status" />
            </x-slot>
            <x-slot name="actions">
                <x-button href="{{ route('admin.activities.edit', $activity) }}" variant="secondary" icon="settings">Modifier</x-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        {{-- Lifecycle actions: each one is only offered when the policy allows it --}}
        <div class="flex flex-wrap items-center gap-3">
            @can('publish', $activity)
                <form method="POST" action="{{ route('admin.activities.publish', $activity) }}">
                    @csrf
                    <x-button icon="check-circle">Publier</x-button>
                </form>
            @endcan
            @can('unpublish', $activity)
                <form method="POST" action="{{ route('admin.activities.unpublish', $activity) }}" onsubmit="return confirm('Dépublier cette activité ? Elle disparaîtra du catalogue, sans être supprimée.');">
                    @csrf
                    <x-button variant="secondary" icon="arrow-left">Dépublier</x-button>
                </form>
            @endcan
            @can('close', $activity)
                <form method="POST" action="{{ route('admin.activities.close', $activity) }}">
                    @csrf
                    <x-button variant="dark" icon="lock">Clôturer les inscriptions</x-button>
                </form>
            @endcan
            @can('cancel', $activity)
                <form method="POST" action="{{ route('admin.activities.cancel', $activity) }}" onsubmit="return confirm('Annuler définitivement cette activité ?');">
                    @csrf
                    <x-button variant="danger-soft" icon="close">Annuler l'activité</x-button>
                </form>
            @endcan
            @can('archive', $activity)
                <form method="POST" action="{{ route('admin.activities.archive', $activity) }}">
                    @csrf
                    <x-button variant="secondary" icon="inbox">Archiver</x-button>
                </form>
            @endcan
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-12">
            <section class="panel p-6 lg:col-span-7">
                <h2 class="panel-label">Description</h2>
                <p class="yc-activity-description yc-activity-description--detail mt-3 whitespace-pre-line text-base leading-relaxed text-slate-700">{{ $activity->description }}</p>

                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-semibold text-slate-900">Début</dt>
                        <dd class="mt-0.5 text-sm text-slate-600">{{ $activity->starts_at?->translatedFormat('d M Y à H:i') ?? 'À préciser' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-900">Fin</dt>
                        <dd class="mt-0.5 text-sm text-slate-600">{{ $activity->ends_at?->translatedFormat('d M Y à H:i') ?? 'À préciser' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-900">Lieu</dt>
                        <dd class="yc-break-anywhere mt-0.5 text-sm text-slate-600">{{ $activity->location ?: 'À préciser' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-900">Date limite d'inscription</dt>
                        <dd class="mt-0.5 text-sm text-slate-600">{{ $activity->registration_deadline?->translatedFormat('d M Y à H:i') ?? 'Aucune' }}</dd>
                    </div>
                    @if ($activity->sourceIdea)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-semibold text-slate-900">Idée d'origine</dt>
                            <dd class="mt-0.5 text-sm">
                                <a href="{{ route('admin.ideas.show', $activity->sourceIdea) }}" class="font-medium text-brand-700 hover:underline">
                                    {{ $activity->sourceIdea->title }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="panel p-6 lg:col-span-5">
                <h2 class="panel-label">Statistiques</h2>
                <dl class="mt-4 space-y-4">
                    <div class="flex items-baseline justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-600">Inscriptions actives</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">{{ $activity->active_registrations_count }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-600">Capacité</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">{{ $activity->capacity ?? '∞' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-600">Équipes</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">{{ $activity->teams->count() }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-sm text-slate-600">Équipes inscrites</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">{{ $activity->teams->where('is_registered', true)->count() }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="panel mt-6">
            <header class="panel-header">
                <h2 class="text-base font-semibold text-slate-900">Participants</h2>
            </header>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Étudiant</th>
                            <th scope="col">Équipe</th>
                            <th scope="col">Inscrit le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activity->activeRegistrations as $registration)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $registration->user->name }}</td>
                                <td class="text-slate-600">{{ $registration->team?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-slate-500">{{ $registration->registered_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-10 text-center text-sm text-slate-500">Aucun participant inscrit pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>

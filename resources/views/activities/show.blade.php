<x-app-layout>
    <x-slot name="title">{{ $activity->title }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header :title="$activity->title" :subtitle="$activity->creator ? 'Proposée par '.$activity->creator->name : null" backHref="{{ route('activities.index') }}" backLabel="Toutes les activités">
            <x-slot name="badge">
                <x-status-badge :status="$activity->status" />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        <div class="grid gap-8 lg:grid-cols-12">
            {{-- Description and practical information --}}
            <div class="lg:col-span-7">
                <section class="panel p-6 sm:p-7">
                    <h2 class="panel-label">Présentation</h2>
                    <p class="yc-activity-description yc-activity-description--detail mt-3 whitespace-pre-line text-base leading-relaxed text-slate-700">{{ $activity->description }}</p>
                </section>

                <section class="panel mt-6 p-6 sm:p-7">
                    <h2 class="panel-label">Informations pratiques</h2>

                    <dl class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="calendar" :size="18" />
                            </span>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Dates</dt>
                                <dd class="mt-0.5 text-sm text-slate-600">
                                    @if ($activity->starts_at)
                                        Du {{ $activity->starts_at->format('d M Y') }}
                                        @if ($activity->ends_at)
                                            au {{ $activity->ends_at->format('d M Y') }}
                                        @endif
                                        <span class="block text-slate-500">
                                            {{ $activity->starts_at->format('H:i') }}{{ $activity->ends_at ? ' – '.$activity->ends_at->format('H:i') : '' }}
                                        </span>
                                    @else
                                        À préciser
                                    @endif
                                </dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="pin" :size="18" />
                            </span>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Lieu</dt>
                            <dd class="yc-break-anywhere mt-0.5 text-sm text-slate-600">{{ $activity->location ?: 'À préciser' }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="clock" :size="18" />
                            </span>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Date limite d'inscription</dt>
                                <dd class="mt-0.5 text-sm text-slate-600">
                                    @if ($activity->registration_deadline)
                                        {{ $activity->registration_deadline->format('d M Y à H:i') }}
                                    @else
                                        Inscription ouverte jusqu'à l'événement
                                    @endif
                                </dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="ticket" :size="18" />
                            </span>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900">Places</dt>
                                <dd class="mt-0.5 text-sm text-slate-600">
                                    @if ($activity->capacity)
                                        {{ $activity->active_registrations_count ?? $activity->filledCount() }} sur {{ $activity->capacity }}
                                    @else
                                        Places illimitées
                                    @endif
                                </dd>
                            </div>
                        </div>

                        @if ($activity->usesTeams())
                            <div class="flex items-start gap-3 sm:col-span-2">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-icon name="users" :size="18" />
                                </span>
                                <div>
                                    <dt class="text-sm font-semibold text-slate-900">Participation en équipe</dt>
                                    <dd class="mt-0.5 text-sm text-slate-600">
                                        Équipe de {{ $activity->min_team_size }} à {{ $activity->max_team_size }} membres.
                                        Créez votre équipe, partagez son code d'invitation, puis inscrivez-la.
                                    </dd>
                                </div>
                            </div>
                        @endif
                    </dl>
                </section>
            </div>

            {{-- Registration panel --}}
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-24">
                    <section class="panel shadow-card p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Inscription</h2>

                        @guest
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Connectez-vous avec un compte étudiant pour vous inscrire à cette activité.
                            </p>
                            <span class="mt-5 block">
                                <x-button class="w-full" icon="lock">Se connecter</x-button>
                            </span>
                        @else
                            @if (auth()->user()->isAdmin())
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Cette activité est gérée depuis l'espace d'administration du BDE.
                                </p>
                                <span class="mt-5 block">
                                    <x-button href="{{ route('admin.activities.show', $activity) }}" variant="secondary" class="w-full">
                                        Ouvrir la vue administration
                                    </x-button>
                                </span>
                            @elseif ($activity->usesTeams())
                                @if ($membership)
                                    @php $team = $membership->team; @endphp
                                    <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50 p-4">
                                        <p class="text-sm font-semibold text-brand-800">Équipe : {{ $team->name }}</p>
                                        <p class="mt-1 text-sm text-brand-700">
                                            Code d'invitation : <span class="font-mono font-bold">{{ $team->join_code }}</span>
                                        </p>
                                        <ul class="mt-3 space-y-1.5">
                                            @foreach ($team->members as $member)
                                                <li class="flex items-center gap-2 text-sm text-brand-800">
                                                    <x-icon name="user" :size="15" class="text-brand-500" />
                                                    <span>{{ $member->user->name }}</span>
                                                    <span class="text-xs text-brand-600">({{ $member->role->label() }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    @if ($team->isLeader(auth()->user()) && ! $team->is_registered)
                                        <form method="POST" action="{{ route('activities.teams.register', [$activity, $team]) }}" class="mt-4">
                                            @csrf
                                            <x-primary-button class="w-full" icon="check-circle">Inscrire mon équipe</x-primary-button>
                                        </form>
                                        <form method="POST" action="{{ route('activities.teams.destroy', [$activity, $team]) }}" class="mt-2" onsubmit="return confirm('Dissoudre cette équipe ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-sm font-semibold text-danger-700 transition-colors duration-150 hover:text-danger-800">
                                                Dissoudre l'équipe
                                            </button>
                                        </form>
                                    @elseif ($team->isLeader(auth()->user()) && $team->is_registered)
                                        <p class="mt-4">
                                            <x-status-badge status="registered" icon="check-circle">Votre équipe est inscrite</x-status-badge>
                                        </p>
                                        <form method="POST" action="{{ route('activities.teams.unregister', [$activity, $team]) }}" class="mt-3" onsubmit="return confirm('Désinscrire votre équipe ?');">
                                            @csrf
                                            <button type="submit" class="w-full text-sm font-semibold text-slate-600 transition-colors duration-150 hover:text-slate-900">
                                                Désinscrire l'équipe
                                            </button>
                                        </form>
                                    @elseif (! $team->is_registered)
                                        <form method="POST" action="{{ route('activities.teams.leave', [$activity, $team]) }}" class="mt-4" onsubmit="return confirm('Quitter cette équipe ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-sm font-semibold text-slate-600 transition-colors duration-150 hover:text-slate-900">
                                                Quitter l'équipe
                                            </button>
                                        </form>
                                    @endif
                                @elseif ($activity->registrationWindowOpen())
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                        Cette activité se fait en équipe. Créez la vôtre ou rejoignez celle de vos coéquipiers.
                                    </p>

                                    <form method="POST" action="{{ route('activities.teams.store', $activity) }}" class="mt-5 space-y-3">
                                        @csrf
                                        <div>
                                            <x-input-label for="name" value="Nom de l'équipe" required />
                                            <x-text-input id="name" name="name" :value="old('name')" required maxlength="80" class="mt-1.5" />
                                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                        </div>
                                        <x-primary-button class="w-full" icon="plus">Créer mon équipe</x-primary-button>
                                    </form>

                                    <div class="my-5 flex items-center gap-3">
                                        <span class="h-px flex-1 bg-slate-200"></span>
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">ou</span>
                                        <span class="h-px flex-1 bg-slate-200"></span>
                                    </div>

                                    <form method="POST" action="{{ route('activities.teams.join', $activity) }}" class="space-y-3">
                                        @csrf
                                        <div>
                                            <x-input-label for="join_code" value="Code d'invitation" required />
                                            <x-text-input id="join_code" name="join_code" :value="old('join_code')" required maxlength="8" class="mt-1.5 font-mono uppercase" />
                                            <x-input-error :messages="$errors->get('join_code')" class="mt-2" />
                                        </div>
                                        <x-secondary-button type="submit" class="w-full" icon="users">Rejoindre l'équipe</x-secondary-button>
                                    </form>
                                @else
                                    <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Les inscriptions sont closes pour cette activité.
                                    </p>
                                @endif
                            @else
                                @if ($registration)
                                    <p class="mt-4">
                                        <x-status-badge status="registered" icon="check-circle">Inscription validée</x-status-badge>
                                    </p>
                                    @if ($activity->cancellation_allowed && $activity->isPublished())
                                        <form method="POST" action="{{ route('activities.registrations.destroy', $activity) }}" class="mt-4" onsubmit="return confirm('Annuler votre inscription ?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-secondary-button type="submit" class="w-full">Annuler mon inscription</x-secondary-button>
                                        </form>
                                    @endif
                                @elseif ($activity->registrationWindowOpen())
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                        Une seule personne par inscription pour cette activité.
                                    </p>
                                    <form method="POST" action="{{ route('activities.registrations.store', $activity) }}" class="mt-5">
                                        @csrf
                                        <x-primary-button class="w-full" icon="check-circle">S'inscrire</x-primary-button>
                                    </form>
                                @else
                                    <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Les inscriptions sont closes pour cette activité.
                                    </p>
                                @endif
                            @endif
                        @endguest
                    </section>

                    @if ($activity->sourceIdea && auth()->user()?->isAdmin())
                        <section class="panel mt-4 p-5">
                            <h2 class="text-sm font-semibold text-slate-900">Idée d'origine</h2>
                            <a href="{{ route('admin.ideas.show', $activity->sourceIdea) }}" class="mt-1 block text-sm font-medium text-brand-700 hover:underline">
                                {{ $activity->sourceIdea->title }}
                            </a>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

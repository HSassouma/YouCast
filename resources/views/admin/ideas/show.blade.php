<x-app-layout width="read">
    <x-slot name="title">{{ $idea->title }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            :title="$idea->title"
            :subtitle="'Proposée par '.($idea->author?->name ?? 'Compte supprimé').' le '.$idea->created_at->translatedFormat('d M Y').($idea->category ? ' · '.$idea->category : '')"
            backHref="{{ route('admin.ideas.index') }}"
            backLabel="Modération des idées">
            <x-slot name="badge">
                <x-status-badge :status="$idea->status" />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        <article class="panel p-6 sm:p-7">
            <h2 class="panel-label">Description</h2>
            <p class="mt-3 whitespace-pre-line text-base leading-relaxed text-slate-700">{{ $idea->description }}</p>

            @if ($idea->review_notes)
                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Notes de modération</p>
                    <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $idea->review_notes }}</p>
                    @if ($idea->reviewer)
                        <p class="mt-2 text-xs text-slate-500">
                            Par {{ $idea->reviewer->name }}
                            @if ($idea->reviewed_at) le {{ $idea->reviewed_at->translatedFormat('d M Y') }} @endif
                        </p>
                    @endif
                </div>
            @endif

            @if ($idea->activity)
                <p class="mt-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800">
                    Déjà transformée en activité :
                    <a href="{{ route('admin.activities.show', $idea->activity) }}" class="font-semibold underline">{{ $idea->activity->title }}</a>
                </p>
            @endif
        </article>

        @can('review', $idea)
            <form method="POST" action="{{ route('admin.ideas.review', $idea) }}" class="panel mt-6 p-6 sm:p-7">
                @csrf

                <h2 class="text-base font-semibold text-slate-900">Décider</h2>
                <p class="mt-1 text-sm text-slate-600">Un retour écrit aide l'étudiant à comprendre la décision.</p>

                <div class="mt-5">
                    <x-input-label for="review_notes" value="Notes de modération" />
                    <x-textarea-input id="review_notes" name="review_notes" rows="4" maxlength="2000" class="mt-1.5">{{ old('review_notes') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('review_notes')" class="mt-2" />
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <x-button name="decision" value="accepted" variant="success" icon="check-circle">Accepter</x-button>
                    <x-button name="decision" value="rejected" variant="danger-soft" icon="close">Refuser</x-button>
                </div>
            </form>
        @endcan

        @can('convert', $idea)
            <form method="POST" action="{{ route('admin.ideas.convert', $idea) }}" class="panel mt-6 p-6 sm:p-7">
                @csrf

                <h2 class="text-base font-semibold text-slate-900">Transformer en activité</h2>
                <p class="mt-1 text-sm text-slate-600">
                    L'activité sera créée en brouillon. L'idée d'origine est conservée.
                </p>

                <div class="mt-5">
                    <x-input-label for="type" value="Type d'activité" required />
                    <x-select-input id="type" name="type" class="mt-1.5">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <x-primary-button icon="sparkles">Créer le brouillon d'activité</x-primary-button>
                </div>
            </form>
        @endcan
    </div>
</x-app-layout>

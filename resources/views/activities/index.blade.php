<x-app-layout>
    <x-slot name="title">Activités — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Activités"
            subtitle="Les sessions publiées par le BDE : événements, défis individuels et défis en équipe.">
            @auth
                <x-slot name="actions">
                    <x-button href="{{ route('dashboard') }}" variant="secondary" icon="grid">Mes inscriptions</x-button>
                </x-slot>
            @endauth
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        <form method="GET" class="panel p-4 sm:p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="q" class="block text-sm font-semibold text-slate-800">Rechercher</label>
                    <x-text-input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Titre, catégorie, lieu" class="mt-1.5" />
                </div>
                <div>
                    <label for="type" class="block text-sm font-semibold text-slate-800">Type</label>
                    <x-select-input id="type" name="type" class="mt-1.5">
                        <option value="">Tous les types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <label for="category" class="block text-sm font-semibold text-slate-800">Catégorie</label>
                    <x-select-input id="category" name="category" class="mt-1.5">
                        <option value="">Toutes</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div class="flex items-end gap-3">
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="open" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked($filters['open'] === '1')>
                        Inscriptions ouvertes
                    </label>
                    <x-button size="lg">Filtrer</x-button>
                </div>
            </div>
        </form>

        @if ($activities->isEmpty())
            <div class="mt-8">
                <x-empty-state title="Aucune activité ne correspond à vos filtres" icon="search">
                    Effacez la recherche ou changez de type pour voir plus de sessions.
                </x-empty-state>
            </div>
        @else
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($activities as $activity)
                    <x-activity-card :activity="$activity" :registered="in_array($activity->id, $registeredActivityIds, true)" />
                @endforeach
            </div>
            <div class="mt-8">{{ $activities->links() }}</div>
        @endif
    </div>
</x-app-layout>

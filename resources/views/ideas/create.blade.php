<x-app-layout width="read">
    <x-slot name="title">Proposer une idée — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Proposer une idée de défi"
            subtitle="Le BDE examine chaque proposition. Les idées acceptées deviennent des activités publiées." />
    </x-slot>

    <div>
        <x-flash />

        <form method="POST" action="{{ route('ideas.store') }}" class="panel space-y-6 p-6 sm:p-7">
            @csrf

            <div>
                <x-input-label for="title" value="Titre" required />
                <x-text-input id="title" name="title" :value="old('title')" required maxlength="180" class="mt-1.5" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="category" value="Catégorie (facultatif)" />
                <x-text-input id="category" name="category" :value="old('category')" maxlength="80" class="mt-1.5" />
                <p class="mt-1.5 text-xs text-slate-500">Web, mobile, IA, design, matériel…</p>
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="description" value="Description" required />
                <x-textarea-input id="description" name="description" rows="8" required maxlength="4000" class="mt-1.5">{{ old('description') }}</x-textarea-input>
                <p class="mt-1.5 text-xs text-slate-500">
                    Expliquez le problème, le public visé et pourquoi cela ferait un bon défi YouCode.
                </p>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
                <x-primary-button icon="send">Envoyer l'idée</x-primary-button>
                <a href="{{ route('ideas.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>

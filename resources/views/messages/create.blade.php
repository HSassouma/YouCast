<x-app-layout width="read">
    <x-slot name="title">Écrire au BDE — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            title="Écrire au BDE"
            subtitle="Votre message reste privé entre vous et l'administration." />
    </x-slot>

    <div>
        <x-flash />

        <form method="POST" action="{{ route('messages.store') }}" class="panel space-y-6 p-6 sm:p-7">
            @csrf

            <div>
                <x-input-label for="subject" value="Objet" required />
                <x-text-input id="subject" name="subject" :value="old('subject')" required maxlength="120" class="mt-1.5" />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="body" value="Message" required />
                <x-textarea-input id="body" name="body" rows="8" required maxlength="4000" class="mt-1.5">{{ old('body') }}</x-textarea-input>
                <p class="mt-1.5 text-xs text-slate-500">Détaillez votre question ou votre demande pour une réponse plus rapide.</p>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
                <x-primary-button icon="send">Envoyer le message</x-primary-button>
                <a href="{{ route('messages.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>

<section aria-labelledby="delete-account-title">
    <h2 id="delete-account-title" class="text-base font-semibold text-danger-800">Supprimer le compte</h2>
    <p class="mt-1 text-sm leading-relaxed text-slate-600">
        La suppression est définitive : votre profil et vos inscriptions seront effacés. Les idées, activités,
        équipes et messages liés à l'histoire du BDE seront conservés, avec l'auteur affiché comme « Compte supprimé ».
    </p>

    <x-danger-button
        class="mt-5"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Supprimer mon compte</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-slate-900">Confirmer la suppression</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Saisissez votre mot de passe pour supprimer définitivement votre compte.
            </p>

            <div class="mt-5">
                <x-input-label for="password" value="Mot de passe" required />
                <x-text-input id="password" name="password" type="password" autocomplete="current-password" class="mt-1.5" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Annuler</x-secondary-button>
                <x-danger-button>Supprimer mon compte</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

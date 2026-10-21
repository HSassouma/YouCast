<section aria-labelledby="update-password-title">
    <h2 id="update-password-title" class="text-base font-semibold text-slate-900">Mot de passe</h2>
    <p class="mt-1 text-sm text-slate-600">
        Choisissez un mot de passe long et aléatoire pour protéger votre compte.
    </p>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="update_password_current_password" value="Mot de passe actuel" required />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1.5" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Nouveau mot de passe" required />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" class="mt-1.5" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmation du mot de passe" required />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1.5" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
            <x-primary-button icon="check-circle">Enregistrer</x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   role="status" class="text-sm font-medium text-success-700">Enregistré.</p>
            @endif
        </div>
    </form>
</section>

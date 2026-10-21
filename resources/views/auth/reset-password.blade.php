<x-guest-layout>
    <x-slot name="title">Nouveau mot de passe — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Nouveau mot de passe</h1>
        <p class="mt-1.5 text-sm text-slate-600">
            Ouvrez le lien reçu par e-mail pour choisir un nouveau mot de passe.
        </p>


        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />

            <div>
                <x-input-label for="password" value="Nouveau mot de passe" required />
                <x-text-input id="password" name="password" type="password" required autofocus autocomplete="new-password" aria-describedby="password-help" class="mt-1.5" />
                <p id="password-help" class="mt-1.5 text-xs text-slate-500">Au moins 8 caractères.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmation du mot de passe" required />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">Enregistrer le nouveau mot de passe</x-primary-button>
        </form>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">Retour à la connexion</a>
        </p>
    </div>
</x-guest-layout>

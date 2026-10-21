<x-guest-layout>
    <x-slot name="title">Créer un compte — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Créer un compte</h1>
        <p class="mt-1.5 text-sm text-slate-600">Inscrivez-vous pour rejoindre les activités du BDE.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="name" value="Nom" required />
                <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus autocomplete="name" class="mt-1.5" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Adresse e-mail" required />
                <x-text-input id="email" name="email" type="email" :value="old('email')" required autocomplete="username" class="mt-1.5" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Mot de passe" required />
                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1.5" />
                <p class="mt-1.5 text-xs text-slate-500">Au moins 8 caractères.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmation du mot de passe" required />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">Créer mon compte</x-primary-button>
        </form>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            Vous avez déjà un compte ?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">Se connecter</a>
        </p>
    </div>
</x-guest-layout>

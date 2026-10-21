<x-guest-layout>
    <x-slot name="title">Mot de passe oublié — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Mot de passe oublié</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
            Indiquez l’adresse e-mail associée à votre compte pour continuer.
        </p>

        @if (session('status'))
            <div id="reset-status" role="status" aria-live="polite" class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.check') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="email" value="Adresse e-mail" required />
                <x-text-input id="email" name="email" type="email" inputmode="email" :value="old('email')" required autofocus autocomplete="email" class="mt-1.5" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">Continuer</x-primary-button>
        </form>

        <p class="mt-3 text-center text-xs text-slate-500">Si un compte correspond à cette adresse, un lien sécurisé lui sera envoyé.</p>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">Retour à la connexion</a>
        </p>
    </div>
</x-guest-layout>

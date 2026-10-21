<x-guest-layout>
    <x-slot name="title">Mot de passe oublié — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Mot de passe oublié</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
            Indiquez l’adresse e-mail associée à votre compte pour continuer.
        </p>

        @if (session('account_missing'))
            <div id="account-missing" role="alert" aria-live="assertive" class="mt-6 flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-800">
                <x-icon name="alert" :size="18" class="mt-0.5 text-danger-600" />
                <div>
                    <p>{{ session('account_missing') }}</p>
                    <a href="{{ route('register') }}" class="mt-2 inline-block font-semibold underline">
                        Créer un compte
                    </a>
                </div>
            </div>
        @endif

        @if (session('reset_error'))
            <div id="reset-error" role="alert" aria-live="assertive" class="mt-6 flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-800">
                <x-icon name="alert" :size="18" class="mt-0.5 text-danger-600" />
                <p>{{ session('reset_error') }}</p>
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

        <p class="mt-3 text-center text-xs text-slate-500">Aucun e-mail ne sera envoyé.</p>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">Retour à la connexion</a>
        </p>
    </div>
</x-guest-layout>

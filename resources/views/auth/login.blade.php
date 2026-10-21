<x-guest-layout>
    <x-slot name="title">Connexion — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Connexion</h1>
        <p class="mt-1.5 text-sm text-slate-600">Retrouvez vos activités et vos inscriptions.</p>

        <x-auth-session-status class="mt-6" :status="session('status')" />

        @if ($errors->has('login'))
            <div id="login-error" role="alert" aria-live="assertive" class="mt-6 flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-800">
                <x-icon name="alert" :size="18" class="mt-0.5 text-danger-600" />
                <p>{{ $errors->first('login') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="email" value="Adresse e-mail" required />
                <x-text-input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" class="mt-1.5" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Mot de passe" required />
                <x-text-input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1.5" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input id="remember_me" type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Se souvenir de moi
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800 hover:underline">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full">Se connecter</x-primary-button>
        </form>

        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">Créer un compte</a>
        </p>
    </div>
</x-guest-layout>

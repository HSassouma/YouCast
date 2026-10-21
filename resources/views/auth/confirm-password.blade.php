<x-guest-layout>
    <x-slot name="title">Zone sécurisée — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Zone sécurisée</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
            Confirmez votre mot de passe pour accéder à cette page.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="password" value="Mot de passe" required />
                <x-text-input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1.5" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">Confirmer</x-primary-button>
        </form>
    </div>
</x-guest-layout>

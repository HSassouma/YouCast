<section aria-labelledby="profile-information-title">
    <h2 id="profile-information-title" class="text-base font-semibold text-slate-900">Informations du compte</h2>
    <p class="mt-1 text-sm text-slate-600">
        Votre nom et votre adresse e-mail sont utilisés par le BDE pour vous contacter.
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="name" value="Nom" required />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" class="mt-1.5" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail" required />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" class="mt-1.5" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-700">
                    Votre adresse e-mail n'est pas vérifiée.
                    <button form="send-verification" class="font-semibold text-brand-700 hover:underline">
                        Renvoyer le lien de vérification.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p role="status" class="mt-2 text-sm font-medium text-success-700">
                        Un nouveau lien de vérification a été envoyé à votre adresse.
                    </p>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
            <x-primary-button icon="check-circle">Enregistrer</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   role="status" class="text-sm font-medium text-success-700">Enregistré.</p>
            @endif
        </div>
    </form>
</section>

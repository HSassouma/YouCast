<x-guest-layout>
    <x-slot name="title">Vérifiez votre adresse — YouCast</x-slot>

    <div class="px-6 py-8 sm:px-8">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Vérifiez votre adresse e-mail</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
            Merci pour votre inscription ! Cliquez sur le lien que nous venons de vous envoyer pour confirmer votre adresse e-mail.
            Si vous ne l'avez pas reçu, nous pouvons vous en renvoyer un.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div role="status" class="mt-6 flex items-start gap-2.5 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-800">
                <x-icon name="check-circle" :size="18" class="mt-0.5 text-success-600" />
                <p>Un nouveau lien de vérification a été envoyé à l'adresse indiquée lors de l'inscription.</p>
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>Renvoyer le lien</x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-slate-900">
                    Se déconnecter
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>

<footer class="border-t border-brand-900 bg-brand-950 text-white">
    <div class="mx-auto max-w-content px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 pb-10 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="Accueil YouCast">
                    <x-application-logo class="h-9 w-9 text-[var(--yc-lime)]" />
                    <span class="font-display text-lg font-bold tracking-tight text-white">YouCast</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/70">
                    Le programme du BDE YouCode et les idées qui le font grandir — au même endroit.
                </p>
                <p class="mt-6 font-display text-sm font-semibold text-[var(--yc-lime)]">Faire. Apprendre. Partager.</p>
            </div>

            <nav aria-label="Liens de pied de page" class="lg:col-span-3">
                <h3 class="text-xs font-bold uppercase tracking-[0.12em] text-white/50">Explorer</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="text-white/75 transition-colors hover:text-white">Accueil</a></li>
                    <li><a href="{{ route('about') }}" class="text-white/75 transition-colors hover:text-white">À propos</a></li>
                </ul>
            </nav>

            <nav aria-label="Compte et accès" class="lg:col-span-3">
                <h3 class="text-xs font-bold uppercase tracking-[0.12em] text-white/50">Mon espace</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @guest
                        <li><a href="{{ route('login') }}" class="text-white/75 transition-colors hover:text-white">Connexion</a></li>
                        <li><a href="{{ route('register') }}" class="text-white/75 transition-colors hover:text-white">Créer un compte</a></li>
                    @else
                        <li><a href="{{ route('dashboard') }}" class="text-white/75 transition-colors hover:text-white">Tableau de bord</a></li>
                        <li><a href="{{ route('profile.edit') }}" class="text-white/75 transition-colors hover:text-white">Mon profil</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-white/75 transition-colors hover:text-white">Se déconnecter</button>
                            </form>
                        </li>
                    @endguest
                </ul>
            </nav>
        </div>

        <div class="flex flex-col gap-2 border-t border-white/15 pt-6 text-sm text-white/55 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} YouCast — BDE YouCode</p>
            <p>La vie du campus se construit ensemble.</p>
        </div>
    </div>
</footer>

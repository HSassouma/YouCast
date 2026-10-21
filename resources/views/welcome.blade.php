<x-site-layout>
    <x-slot name="title">YouCast — La vie du BDE, côté pratique</x-slot>

    <section class="yc-home-hero relative overflow-hidden">
        <div class="mx-auto max-w-content px-4 pb-16 pt-14 sm:px-6 sm:pb-20 sm:pt-20 lg:px-8 lg:pb-24 lg:pt-24">
            <div class="grid items-center gap-12 lg:grid-cols-[1.08fr_.92fr] lg:gap-20">
                <div class="max-w-2xl">
                    <p class="yc-enter inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-brand-800" style="--yc-delay:.04s">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[var(--yc-coral)]"></span>
                        Le BDE YouCode, en mouvement
                    </p>

                    <h1 class="yc-enter mt-7 max-w-[13ch] font-display text-5xl font-bold leading-[0.99] tracking-[-0.055em] text-slate-900 sm:text-6xl lg:text-[5.25rem]" style="--yc-delay:.1s">
                        On apprend <span class="text-brand-700">en faisant.</span>
                        <span class="block">On avance ensemble.</span>
                    </h1>

                    <p class="yc-enter mt-7 max-w-xl text-lg leading-relaxed text-slate-600 sm:text-xl" style="--yc-delay:.18s">
                        Challenges, rencontres et projets du BDE&nbsp;: trouve ta prochaine activité,
                        rejoins une équipe et fais partie de ce qui se construit sur le campus.
                    </p>

                    <div class="yc-enter mt-9 flex flex-col gap-3 sm:flex-row sm:items-center" style="--yc-delay:.26s">
                        @guest
                            <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-brand-800 px-6 text-sm font-bold text-white transition hover:bg-brand-900">
                                Rejoindre YouCast
                                <x-icon name="arrow-right" :size="17" />
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-slate-300 bg-transparent px-6 text-sm font-bold text-slate-800 transition hover:bg-white">
                                J’ai déjà un compte
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-brand-800 px-6 text-sm font-bold text-white transition hover:bg-brand-900">
                                Aller à mon espace
                                <x-icon name="arrow-right" :size="17" />
                            </a>
                            <a href="{{ route('about') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-slate-300 px-6 text-sm font-bold text-slate-800 transition hover:bg-white">
                                Découvrir YouCast
                            </a>
                        @endguest
                    </div>

                    <div class="yc-enter mt-9 flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-300 pt-5 text-sm text-slate-600" style="--yc-delay:.34s">
                        <span class="inline-flex items-center gap-2"><x-icon name="users" :size="16" class="text-brand-700" /> Apprendre entre pairs</span>
                        <span class="inline-flex items-center gap-2"><x-icon name="bulb" :size="16" class="text-brand-700" /> Faire grandir les idées</span>
                    </div>
                </div>

                <div class="yc-home-art yc-enter relative overflow-hidden rounded-lg p-7 shadow-none sm:p-9 lg:p-10" style="--yc-delay:.2s">
                    <div class="relative flex items-center justify-between gap-4 border-b border-white/20 pb-5">
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-white/70">Le cycle YouCode</p>
                        <span class="yc-highlight inline-flex h-10 w-10 items-center justify-center rounded-full font-display text-sm font-bold">YC</span>
                    </div>

                    <ol class="relative mt-2">
                        <li class="yc-home-step grid grid-cols-[3.25rem_1fr] gap-4 py-5 sm:py-6">
                            <span class="yc-home-step-number pt-0.5 font-display text-2xl font-bold">01</span>
                            <div>
                                <h2 class="font-display text-xl font-bold text-white sm:text-2xl">Une idée prend forme.</h2>
                                <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-white/70">Chacun apporte son regard et propose ce qui pourrait rassembler la promo.</p>
                            </div>
                        </li>
                        <li class="yc-home-step grid grid-cols-[3.25rem_1fr] gap-4 py-5 sm:py-6">
                            <span class="yc-home-step-number pt-0.5 font-display text-2xl font-bold">02</span>
                            <div>
                                <h2 class="font-display text-xl font-bold text-white sm:text-2xl">On construit à plusieurs.</h2>
                                <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-white/70">Défis, ateliers et projets deviennent des expériences concrètes.</p>
                            </div>
                        </li>
                        <li class="yc-home-step grid grid-cols-[3.25rem_1fr] gap-4 py-5 sm:py-6">
                            <span class="yc-home-step-number pt-0.5 font-display text-2xl font-bold">03</span>
                            <div>
                                <h2 class="font-display text-xl font-bold text-white sm:text-2xl">Le savoir circule.</h2>
                                <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-white/70">On partage, on s’entraide et on apprend aussi en transmettant.</p>
                            </div>
                        </li>
                    </ol>

                    <p class="relative mt-3 border-t border-white/20 pt-5 text-sm font-semibold text-[var(--yc-lime)]">
                        Pensé pour les apprenants. Porté par la communauté.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-content px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="grid gap-10 lg:grid-cols-[.72fr_1.28fr] lg:gap-20">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-800">L’esprit du campus</p>
                <h2 class="mt-4 max-w-md font-display text-3xl font-bold leading-tight tracking-tight text-slate-900 sm:text-4xl">
                    La meilleure façon d’apprendre, c’est de faire.
                </h2>
            </div>
            <div>
                <p class="max-w-2xl text-lg leading-relaxed text-slate-700">
                    YouCode met la personne au centre, encourage les projets concrets et fait de l’entraide une force.
                    YouCast prolonge cet esprit dans la vie du BDE&nbsp;: proposer, participer et réaliser ensemble.
                </p>
                <a href="{{ route('about') }}" class="mt-6 inline-flex items-center gap-2 border-b-2 border-brand-700 pb-1 text-sm font-bold text-brand-800 transition-colors hover:border-[var(--yc-coral)] hover:text-brand-700">
                    L’histoire de la plateforme
                    <x-icon name="arrow-right" :size="16" />
                </a>
            </div>
        </div>

        <div class="mt-14 grid border-y border-slate-300 sm:grid-cols-3">
            <article class="py-7 sm:pe-7 sm:py-8">
                <span class="font-display text-3xl font-bold text-[var(--yc-coral)]">01</span>
                <h3 class="mt-3 font-display text-xl font-bold text-slate-900">Proposer</h3>
                <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-600">Une idée de défi ou d’atelier peut devenir le prochain rendez-vous du BDE.</p>
            </article>
            <article class="border-t border-slate-300 py-7 sm:border-l sm:border-t-0 sm:px-7 sm:py-8">
                <span class="font-display text-3xl font-bold text-[var(--yc-coral)]">02</span>
                <h3 class="mt-3 font-display text-xl font-bold text-slate-900">Participer</h3>
                <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-600">Les activités rassemblent les apprenants autour de l’expérience et du jeu.</p>
            </article>
            <article class="border-t border-slate-300 py-7 sm:border-l sm:border-t-0 sm:ps-7 sm:py-8">
                <span class="font-display text-3xl font-bold text-[var(--yc-coral)]">03</span>
                <h3 class="mt-3 font-display text-xl font-bold text-slate-900">Partager</h3>
                <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-600">En solo ou en équipe, chacun contribue et apprend des autres.</p>
            </article>
        </div>
    </section>

    <section class="bg-brand-950 text-white">
        <div class="mx-auto flex max-w-content flex-col gap-7 px-4 py-12 sm:px-6 sm:py-14 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--yc-lime)]">À toi de jouer</p>
                <h2 class="mt-3 font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">La prochaine idée peut venir de toi.</h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/70">Rejoins l’espace du BDE pour découvrir le programme, partager une idée et faire équipe.</p>
            </div>
            @guest
                <a href="{{ route('register') }}" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md px-6 text-sm font-bold text-slate-900 transition hover:brightness-95" style="background:var(--yc-lime)">
                    Créer mon compte <x-icon name="arrow-right" :size="17" />
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md px-6 text-sm font-bold text-slate-900 transition hover:brightness-95" style="background:var(--yc-lime)">
                    Mon espace <x-icon name="arrow-right" :size="17" />
                </a>
            @endguest
        </div>
    </section>
</x-site-layout>

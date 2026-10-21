<x-site-layout>
    <x-slot name="title">À propos — YouCast</x-slot>

    <section class="border-b border-brand-100 bg-brand-50">
        <div class="mx-auto grid max-w-content gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[.7fr_1.3fr] lg:gap-20 lg:px-8 lg:py-24">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-800">Pourquoi YouCast</p>
                <p class="mt-5 font-display text-sm font-semibold text-slate-500">BDE YouCode · Espace commun</p>
            </div>
            <div>
                <h1 class="max-w-4xl font-display text-4xl font-bold leading-[1.02] tracking-[-0.05em] text-slate-900 sm:text-5xl lg:text-6xl">
                    Les idées prennent vie quand on les fait ensemble.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-700">
                    YouCast rassemble les activités, les inscriptions et les propositions du BDE dans un espace simple,
                    pour que chacun puisse passer de l’envie à l’expérience.
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-content px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[.7fr_1.3fr] lg:gap-20">
            <h2 class="max-w-sm font-display text-2xl font-bold leading-tight tracking-tight text-slate-900 sm:text-3xl">
                Une plateforme inspirée de la façon d’apprendre à YouCode.
            </h2>
            <div class="max-w-3xl space-y-5 text-base leading-relaxed text-slate-700">
                <p>
                    YouCode met l’apprenant au centre, privilégie la pratique et relie les apprentissages à des projets concrets.
                    L’entraide et le partage entre pairs font partie de cette expérience.
                </p>
                <p>
                    YouCast prolonge ces principes dans la vie du campus. Le BDE peut publier ses activités,
                    les apprenants peuvent s’y inscrire seuls ou en équipe, et les idées de la communauté peuvent
                    devenir de nouveaux rendez-vous.
                </p>
                <p>
                    L’outil reste volontairement direct&nbsp;: découvrir, proposer, participer et garder le fil avec l’organisation.
                </p>
            </div>
        </div>

        <div class="mt-14 border-y border-slate-300">
            @foreach ([
                ['01', 'Pratiquer', 'Des ateliers et défis pour transformer la curiosité en expérience concrète.'],
                ['02', 'Apprendre entre pairs', 'Les équipes se construisent autour de l’entraide et de ce que chacun apporte.'],
                ['03', 'Faire circuler les idées', 'Une proposition peut être examinée par le BDE puis devenir une activité réelle.'],
            ] as [$number, $title, $description])
                <article class="grid gap-3 border-b border-slate-300 py-6 last:border-b-0 sm:grid-cols-[4rem_1fr_1fr] sm:items-start sm:gap-6 sm:py-7">
                    <span class="font-display text-xl font-bold text-[var(--yc-coral)]">{{ $number }}</span>
                    <h3 class="font-display text-xl font-bold text-slate-900">{{ $title }}</h3>
                    <p class="max-w-lg text-sm leading-relaxed text-slate-600">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-brand-950 text-white">
        <div class="mx-auto grid max-w-content gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[1fr_auto] lg:items-center lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--yc-lime)]">Une idée en tête&nbsp;?</p>
                <h2 class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Fais-la entrer dans le programme.</h2>
                <p class="mt-3 text-sm leading-relaxed text-white/70">Rejoins YouCast pour proposer une activité et suivre les nouvelles du BDE.</p>
            </div>
            @guest
                <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md px-6 text-sm font-bold text-slate-900 transition hover:brightness-95" style="background:var(--yc-lime)">
                    Rejoindre YouCast <x-icon name="arrow-right" :size="17" />
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md px-6 text-sm font-bold text-slate-900 transition hover:brightness-95" style="background:var(--yc-lime)">
                    Aller à mon espace <x-icon name="arrow-right" :size="17" />
                </a>
            @endguest
        </div>
    </section>
</x-site-layout>

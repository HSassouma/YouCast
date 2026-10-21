<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-dvh bg-page font-sans text-slate-900 antialiased">
        <a href="#main" class="skip-link">Aller au contenu principal</a>
        <div class="grid min-h-dvh lg:grid-cols-[minmax(0,1.05fr)_minmax(28rem,.95fr)]">
            <aside class="relative hidden overflow-hidden bg-brand-950 px-10 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-16 xl:py-12">
                <a href="{{ route('home') }}" class="relative flex items-center gap-3" aria-label="Accueil YouCast">
                    <x-application-logo class="h-11 w-11 text-[var(--yc-lime)]" />
                    <span class="font-display text-xl font-bold tracking-tight">YouCast</span>
                </a>

                <div class="relative max-w-xl py-16">
                    <p class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-[var(--yc-lime)]">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[var(--yc-coral)]"></span>
                        BDE YouCode
                    </p>
                    <h1 class="mt-7 font-display text-5xl font-bold leading-[1.02] tracking-[-0.05em] xl:text-6xl">
                        Apprendre en faisant. <span class="text-[var(--yc-lime)]">Avancer ensemble.</span>
                    </h1>
                    <p class="mt-6 max-w-md text-lg leading-relaxed text-white/70">
                        Les activités, les idées et les équipes qui font vivre le campus.
                    </p>

                    <div class="mt-10 grid max-w-lg grid-cols-3 border-y border-white/20 py-4 text-xs font-semibold text-white/70">
                        <span class="border-r border-white/20 pe-3">Proposer</span>
                        <span class="border-r border-white/20 px-3">Participer</span>
                        <span class="ps-3">Partager</span>
                    </div>
                </div>

                <p class="relative text-xs text-white/50">YouCast · La plateforme du BDE YouCode</p>
            </aside>

            <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10 sm:px-8 lg:px-12">
                <div class="app-page-enter w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-7 flex items-center justify-center gap-2.5 text-brand-800 lg:hidden" aria-label="Accueil YouCast">
                        <x-application-logo class="h-10 w-10" />
                        <span class="font-display text-xl font-bold tracking-tight">YouCast</span>
                    </a>

                    <main id="main" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-none">
                        {{ $slot }}
                    </main>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        BDE YouCode · plateforme des activités étudiantes
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>

@php
    $user = Auth::user();
    $messagesRoute = $user?->isAdmin() ? route('admin.messages.index') : route('messages.index');
@endphp

<nav x-data="{ open: false, solid: false }"
     x-init="solid = window.scrollY > 16"
     @scroll.window.passive="solid = window.scrollY > 16"
     :class="{ 'sn-solid': solid || open }"
     class="site-nav {{ $overDark ? 'sn-ink-dark' : 'sn-ink-light' }}">
    <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">
            {{-- Brand --}}
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="Accueil YouCast">
                <x-application-logo class="h-9 w-9 sn-logo" />
                <span class="sn-brand text-base font-bold tracking-tight">YouCast</span>
            </a>

            {{-- Primary links --}}
            <div class="hidden items-center gap-1 lg:flex">
                <a href="{{ route('home') }}" class="sn-link {{ request()->routeIs('home') ? 'sn-link-active' : '' }}">
                    Accueil
                </a>
                <a href="{{ route('about') }}" class="sn-link {{ request()->routeIs('about') ? 'sn-link-active' : '' }}">
                    À propos
                </a>
                @auth
                    <a href="{{ route('activities.index') }}" class="sn-link {{ request()->routeIs('activities.*') ? 'sn-link-active' : '' }}">
                        Activités
                    </a>
                    <a href="{{ route('dashboard') }}" class="sn-link">Tableau de bord</a>
                @endauth
            </div>

            {{-- Account area --}}
            <div class="flex items-center gap-2">
                @guest
                    <a href="{{ route('login') }}" class="sn-link hidden sm:inline-flex">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" class="sn-cta">
                        Créer un compte
                    </a>
                @else
                    <div class="hidden lg:block">
                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <span class="sn-identity flex h-8 w-8 items-center justify-center rounded-full bg-brand-500/25 text-sm font-bold text-inherit ring-1 ring-inset sn-identity-ring">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                </span>
                                <span class="sn-user max-w-[9rem] truncate text-sm font-medium">{{ $user->name }}</span>
                                <x-icon name="chevron-down" :size="16" />
                            </x-slot>
                            <x-slot name="content">
                                <div class="border-b border-slate-100 px-3.5 py-2.5">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    <p class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-wide text-brand-700">
                                        <x-icon name="shield" :size="12" />
                                        {{ $user->role->label() }}
                                    </p>
                                </div>
                                <div class="pt-1.5">
                                    <x-dropdown-link :href="route('profile.edit')" icon="user">Mon profil</x-dropdown-link>
                                    <x-dropdown-link :href="$messagesRoute" icon="inbox">Mes messages</x-dropdown-link>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm text-slate-700 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900">
                                            <x-icon name="logout" :size="17" class="text-slate-400" />
                                            Se déconnecter
                                        </button>
                                    </form>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    <a href="{{ route('dashboard') }}" class="sn-cta hidden sm:inline-flex lg:hidden">
                        Tableau de bord
                    </a>
                @endguest

                {{-- Mobile toggle --}}
                <button type="button"
                        @click="open = ! open"
                        @keydown.escape="open = false"
                        :aria-expanded="open"
                        aria-controls="site-mobile-navigation"
                        class="sn-burger inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg transition-colors duration-300 lg:hidden">
                    <span class="sr-only">Ouvrir le menu</span>
                    <x-icon name="menu" :size="22" x-show="! open" />
                    <x-icon name="close" :size="22" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile panel --}}
    <div id="site-mobile-navigation"
         x-show="open"
         x-cloak
         @keydown.escape="open = false"
         class="border-t border-slate-200 bg-white shadow-soft lg:hidden">
        <div class="mx-auto max-w-content space-y-1 px-4 py-4 sm:px-6">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Accueil</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('about')" :active="request()->routeIs('about')" icon="info">À propos</x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('activities.index')" :active="request()->routeIs('activities.*')" icon="calendar">Activités</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">Tableau de bord</x-responsive-nav-link>
                <x-responsive-nav-link :href="$messagesRoute" :active="request()->routeIs('messages.*') || request()->routeIs('admin.messages.*')" icon="message">Mes messages</x-responsive-nav-link>

                <div class="mt-3 border-t border-slate-100 pt-3">
                    <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Mon profil</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900">
                            <x-icon name="logout" :size="18" />
                            Se déconnecter
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <x-responsive-nav-link :href="route('login')" icon="lock">Se connecter</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')" icon="plus">Créer un compte</x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>

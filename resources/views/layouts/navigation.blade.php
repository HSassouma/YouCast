@php
    $user = Auth::user();
    $messagesRoute = $user?->isAdmin() ? route('admin.messages.index') : route('messages.index');
    $unreadMessages = $unreadMessages ?? 0;
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-brand-100/70 bg-white/85 shadow-soft backdrop-blur-xl">
    <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            {{-- Brand --}}
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="Accueil YouCast">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-base font-bold tracking-tight text-slate-900">YouCast</span>
                </a>
                @guest
                    <span class="hidden truncate border-slate-200 text-sm text-slate-500 lg:block lg:border-l lg:ps-3">
                        Les activités du BDE YouCode
                    </span>
                @endguest
            </div>

            {{-- Primary navigation --}}
            <div class="hidden items-center gap-1 lg:flex">
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Accueil</x-nav-link>

                @auth
                    <x-nav-link :href="route('activities.index')" :active="request()->routeIs('activities.*')" icon="calendar">Activités</x-nav-link>
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">Tableau de bord</x-nav-link>
                    <x-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.*')" icon="bulb">Mes idées</x-nav-link>

                    @if ($user->isAdmin())
                        <x-nav-link :href="route('admin.activities.index')" :active="request()->routeIs('admin.activities.*')" icon="settings">Gestion</x-nav-link>
                        <x-nav-link :href="route('admin.ideas.index')" :active="request()->routeIs('admin.ideas.*')" icon="flag">Modération</x-nav-link>
                    @endif

                    <x-nav-link :href="$messagesRoute" :active="request()->routeIs('messages.*') || request()->routeIs('admin.messages.*')" icon="message">
                        Messages
                        @if ($unreadMessages > 0)
                            <span class="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[0.6875rem] font-bold text-white">
                                {{ $unreadMessages > 99 ? '99+' : $unreadMessages }}
                            </span>
                        @endif
                    </x-nav-link>
                @endauth
            </div>

            {{-- Account area --}}
            <div class="flex items-center gap-2">
                @guest
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900 sm:inline-flex">
                        Se connecter
                    </a>
                    <x-button href="{{ route('register') }}">Créer un compte</x-button>
                @else
                    <div class="hidden lg:block">
                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[10rem] truncate text-sm font-medium text-slate-700">{{ $user->name }}</span>
                                <x-icon name="chevron-down" :size="16" class="text-slate-400" />
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
                                    @if ($user->isAdmin())
                                        <x-dropdown-link :href="route('admin.messages.index')" icon="inbox">Boîte de réception</x-dropdown-link>
                                    @else
                                        <x-dropdown-link :href="route('messages.index')" icon="inbox">Mes messages</x-dropdown-link>
                                    @endif
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
                @endguest

                {{-- Mobile toggle --}}
                <button type="button"
                        @click="open = ! open"
                        @keydown.escape="open = false"
                        :aria-expanded="open"
                        aria-controls="mobile-navigation"
                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:bg-slate-100 lg:hidden">
                    <span class="sr-only">Ouvrir le menu</span>
                    <x-icon name="menu" :size="22" x-show="! open" />
                    <x-icon name="close" :size="22" x-show="open" x-cloak />
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile panel --}}
    <div id="mobile-navigation" x-show="open" x-cloak @keydown.escape="open = false"
         class="border-t border-slate-200 bg-white lg:hidden">
        <div class="mx-auto max-w-content space-y-1 px-4 py-4 sm:px-6">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Accueil</x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('activities.index')" :active="request()->routeIs('activities.*')" icon="calendar">Activités</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">Tableau de bord</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('ideas.index')" :active="request()->routeIs('ideas.*')" icon="bulb">Mes idées</x-responsive-nav-link>

                @if ($user->isAdmin())
                    <x-responsive-nav-link :href="route('admin.activities.index')" :active="request()->routeIs('admin.activities.*')" icon="settings">Gestion des activités</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.ideas.index')" :active="request()->routeIs('admin.ideas.*')" icon="flag">Modération des idées</x-responsive-nav-link>
                @endif

                <x-responsive-nav-link :href="$messagesRoute" :active="request()->routeIs('messages.*') || request()->routeIs('admin.messages.*')" icon="message">
                    Messages
                    @if ($unreadMessages > 0)
                        <span class="ms-auto inline-flex min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[0.6875rem] font-bold text-white">
                            {{ $unreadMessages > 99 ? '99+' : $unreadMessages }}
                        </span>
                    @endif
                </x-responsive-nav-link>

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

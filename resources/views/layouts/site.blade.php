@php
    // `nav-theme="dark"` marks pages whose hero is dark, so the navigation
    // starts with white ink over it (home). Everything else stays light.
    $overDarkNav = $attributes->get('nav-theme', 'light') === 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-dvh bg-page font-sans text-slate-900 antialiased">
        <a href="#main" class="skip-link">Aller au contenu principal</a>

        <div class="flex min-h-dvh flex-col">
            <x-site-nav :over-dark="$overDarkNav" />

            <main id="main" class="flex-1">
                {{ $slot }}
            </main>

            <x-site-footer />
        </div>
    </body>
</html>

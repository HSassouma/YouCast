@php
    // Content width tokens. The page header and the main measure share the
    // same width so titles always align with the content beneath them:
    //   full → 76 rem  dashboards, catalogues, management tables
    //   wide → 56 rem  inboxes and offset lists
    //   read → 48 rem  forms, conversation threads, review panels
    $widths = [
        'full' => 'max-w-content',
        'wide' => 'max-w-4xl',
        'read' => 'max-w-3xl',
    ];
    $widthClass = $widths[$attributes->get('width', 'full')] ?? $widths['full'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-dvh bg-page font-sans text-slate-900 antialiased">
        <a href="#main" class="skip-link">Aller au contenu principal</a>

        <div class="flex min-h-dvh flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="yc-admin-heading border-b border-brand-100/80">
                    <div class="mx-auto {{ $widthClass }} px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="main" class="flex-1">
                <div class="app-page-enter mx-auto {{ $widthClass }} px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>

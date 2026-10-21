@props(['name', 'size' => 20])

@php
    /**
     * A single outline icon family: 24×24 grid, 1.75 stroke, round joins.
     * Keeping the set in one place is what makes the icons look like a family
     * instead of a collection of borrowed glyphs.
     */
    $icons = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
        'pin' => '<path d="M20 10c0 4.6-6 12-6 12S8 14.6 8 10a6 6 0 1 1 12 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'users' => '<path d="M16 19v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V19"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M17 4.6a3.5 3.5 0 0 1 0 6.8M21 19v-1.5a4 4 0 0 0-3-3.87"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'bulb' => '<path d="M9.5 18h5M10.5 21h3M12 3a6 6 0 0 1 3.6 10.8c-.6.5-.9 1-.9 1.7v.5H9.3v-.5c0-.7-.3-1.2-.9-1.7A6 6 0 0 1 12 3Z"/>',
        'message' => '<path d="M20 15.5a2.5 2.5 0 0 1-2.5 2.5H8l-4 3V6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5Z"/>',
        'inbox' => '<path d="M4 13h4l1.5 3h5L16 13h4"/><path d="M5.4 5h13.2a2 2 0 0 1 1.9 1.4L22 13v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4l1.5-6.6A2 2 0 0 1 5.4 5Z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'check' => '<path d="m5 13 4 4L19 7"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'alert' => '<path d="M12 4.5 2.8 20h18.4L12 4.5Z"/><path d="M12 10v4.5M12 17.4h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'logout' => '<path d="M15 17l5-5-5-5M20 12H9"/><path d="M12 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34h.08a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87v.08a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.55 1Z"/>',
        'grid' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'send' => '<path d="M21 3 10.5 13.5M21 3l-6.5 18-4-8-8-4Z"/>',
        'ticket' => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 0 0 4 2 2 0 0 1-2 2H6a2 2 0 0 1-2-2 2 2 0 0 0 0-4 2 2 0 0 0 0-4Z"/><path d="M13 6v2M13 11v2M13 16v2"/>',
        'shield' => '<path d="M12 3 5 6v5.5c0 4.3 3 8.2 7 9.5 4-1.3 7-5.2 7-9.5V6Z"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'sparkles' => '<path d="M11 3l1.4 3.9L16 8.3l-3.6 1.4L11 13.6l-1.4-3.9L6 8.3l3.6-1.4Z"/><path d="M18 13.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8Z"/>',
        'flag' => '<path d="M5 21V4.5M5 5h11l-1.5 3L16 11H5"/>',
        'chart' => '<path d="M3 20h18"/><path d="M6.5 20v-5.5M11 20V8.5M15.5 20v-8M20 20V4.5"/>',
        'home' => '<path d="m4 10 8-6.5 8 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19Z"/><path d="M9.5 20.5v-6h5v6"/>',
    ];

    $path = $icons[$name] ?? $icons['info'];
@endphp

<svg {{ $attributes->merge(['class' => 'shrink-0']) }}
     width="{{ $size }}"
     height="{{ $size }}"
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.75"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true"
     focusable="false">{!! $path !!}</svg>

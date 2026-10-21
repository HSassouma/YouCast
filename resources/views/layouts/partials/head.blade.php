<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="YouCast — la plateforme des activités, des équipes et des idées du BDE YouCode.">
<title>{{ $title ?? config('app.name', 'YouCast') }}</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
<link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Motion bootstrap, executed before the first paint. The class merely
     unlocks the `[data-reveal]` styles; it is never set when the visitor
     prefers reduced motion, so that mode never hides any content. --}}
<script>
    (function () {
        var root = document.documentElement;
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches
            && 'IntersectionObserver' in window) {
            root.classList.add('yc-motion-ready');
        }
    })();
</script>

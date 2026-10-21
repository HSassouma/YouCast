import Alpine from 'alpinejs';

/**
 * Scroll reveal.
 *
 * Elements marked with `data-reveal` fade in as they enter the viewport.
 * The whole mechanism is gated behind the `.yc-motion-ready` class that the
 * inline bootstrap in the layouts sets before the first paint — and only
 * when the visitor has NOT requested reduced motion. The hidden state
 * therefore never applies in the animation-free experience, and if this
 * bundle fails to load nothing on the page stays hidden either.
 */
if (document.documentElement.classList.contains('yc-motion-ready')) {
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('yc-revealed');
                observer.unobserve(entry.target);
            }
        }
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -8% 0px',
    });

    document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
}

window.Alpine = Alpine;

Alpine.start();

/**
 * Layout behaviour needed on every page: offline status, scroll reveals, and
 * the cinematic footer.
 *
 * This was three separate inline <script> blocks in the layout and footer
 * Blade templates. As inline blocks they were re-parsed on every page view and
 * could not be cached apart from the document. As modules they load in
 * parallel with the rest of the bundle and are cached across navigations.
 *
 * Kept out of app.js deliberately: contact.js and insights.js import nothing
 * from here, and a shared module would drag this onto pages that have no
 * footer to reveal.
 */

// ── Offline status ──────────────────────────────────────────────────────
const offlineStatus = document.getElementById('offline-status');

if (offlineStatus) {
    const updateOfflineStatus = () => {
        offlineStatus.hidden = navigator.onLine;
    };

    window.addEventListener('online', updateOfflineStatus);
    window.addEventListener('offline', updateOfflineStatus);
    updateOfflineStatus();
}

// ── Scroll reveals ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const targets = document.querySelectorAll('.animate-on-scroll');
    if (!targets.length) return;

    // Respect reduced motion from the start: no observer, no opacity:0
    // dependency, nothing to un-observe.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        targets.forEach((el) => el.classList.add('visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('visible');
                // This is a one-shot reveal. Stop observing each element
                // once it has fired, otherwise the observer retains a
                // strong reference to every animated node for the whole
                // page lifetime and keeps waking up on every scroll tick
                // even though nothing else can change.
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    targets.forEach((el) => observer.observe(el));

    // Last-ditch sweep. Anything still hidden after a few seconds is
    // either off-screen and legitimately pending, or the observer
    // missed it; either way we must not leave content invisible if the
    // callback never runs (e.g. print, or a browser quirk).
    setTimeout(() => {
        targets.forEach((el) => {
            if (!el.classList.contains('visible')) {
                el.classList.add('visible');
                observer.unobserve(el);
            }
        });
    }, 3000);
});

// ── Cinematic footer ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const footer = document.querySelector('.wx-cinematic-footer');
    if (!footer || !('IntersectionObserver' in window)) {
        footer?.classList.add('is-revealed');
        return;
    }

    const observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                footer.classList.add('is-revealed');
                observer.disconnect();
            }
        },
        { threshold: 0.12 }
    );

    observer.observe(footer);
});
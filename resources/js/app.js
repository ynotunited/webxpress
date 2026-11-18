import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

import './layout';

Alpine.plugin(focus);
window.Alpine = Alpine;

/**
 * Alpine dispatches alpine:init synchronously when start() runs, and that is
 * where per-page entries (contact.js, insights.js, testimonials.js) register
 * their components. Calling start() at module scope made registration depend on
 * module execution order: if this file ran before a page entry, that entry's
 * listener was attached after the event had already fired, the component was
 * never registered, and x-data="startProject()" threw on that page.
 *
 * The subtlety is that waiting is not as simple as checking for 'loading'.
 * Module scripts are deferred, so by the time this file evaluates the document
 * has finished parsing and readyState is already 'interactive' — while the
 * remaining page entries, later in document order, have still not run. Starting
 * on 'interactive' therefore reproduces the original race: Alpine comes up
 * before contact.js has defined anything.
 *
 * DOMContentLoaded is the only event that is guaranteed to fire after every
 * deferred script has executed, so wait for that unless the document is already
 * fully loaded — in which case nothing further can arrive and waiting would
 * hang forever.
 */
const startAlpine = () => Alpine.start();

if (document.readyState === 'complete') {
    startAlpine();
} else {
    document.addEventListener('DOMContentLoaded', startAlpine, { once: true });
}

// Alpine evaluates expressions with `unsafe-eval`, which is intentionally
// prohibited by the site's Content Security Policy. Keep the Services
// disclosure dependency-free so it remains interactive under that policy.
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('[data-services-trigger]');
    const menu = document.querySelector('[data-services-menu]');

    if (!trigger || !menu) return;

    const closeMenu = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
    };

    trigger.addEventListener('click', () => {
        menu.hidden ? openMenu() : closeMenu();
    });

    document.addEventListener('click', (event) => {
        if (!menu.hidden && !menu.contains(event.target) && !trigger.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || menu.hidden) return;
        closeMenu();
        trigger.focus();
    });
});

// Give every interaction immediate feedback, including native links and forms.
const clearPressed = () => {
    for (const el of document.querySelectorAll('.is-pressed')) {
        el.classList.remove('is-pressed');
    }
};

document.addEventListener('pointerdown', (event) => {
    const control = event.target.closest('a, button, [role="button"]');
    if (control && !control.hasAttribute('disabled')) {
        control.classList.add('is-pressed');
    }
});

document.addEventListener('pointerup', clearPressed);
document.addEventListener('pointercancel', clearPressed);
window.addEventListener('blur', clearPressed);

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || link.target === '_blank' || link.href.startsWith('#')) return;
    // Left-click only: modified clicks open a new tab and never navigate away.
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

    link.setAttribute('aria-busy', 'true');
    link.classList.add('is-loading');
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img').forEach((image) => {
        if (image.complete) {
            image.classList.add(image.naturalWidth > 0 ? 'is-loaded' : 'is-image-missing');
            return;
        }
        image.addEventListener('load', () => image.classList.add('is-loaded'), { once: true });
        image.addEventListener('error', () => image.classList.add('is-image-missing'), { once: true });
    });

    if ('serviceWorker' in navigator) {
        // The version query rolls the worker's cache on every deploy.
        navigator.serviceWorker
            .register(`/sw.js?v=${window.APP_VERSION || 'dev'}`)
            .catch((error) => console.warn('Service worker registration failed.', error));
    }
});

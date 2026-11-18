/**
 * The client-voices pager.
 *
 * This replaces an auto-scrolling marquee that built a seamless loop by
 * rendering every review twice and translating the track forever. Three
 * problems with that: the strip grew without bound as reviews were added, the
 * duplicate copy doubled the reading cost, and a quote that drifted past the
 * viewport while reading was simply gone. Paging shows a fixed set with
 * explicit back/forward control, so nothing moves while it is being read and
 * every voice is reachable by keyboard.
 *
 * The cards themselves stay in the HTML. This component only decides which of
 * them are on the current set, so the quotes are still readable without
 * JavaScript — where every review simply stacks — and a failure here degrades
 * to a plain list rather than an empty section.
 *
 * Its own entry point for the same reason as contact.js and insights.js: it is
 * behaviour, not content, so it has no business being inline markup that gets
 * re-sent with every uncached visit to the homepage.
 */
/**
 * Client-voices pager.
 *
 * This intentionally uses DOM APIs instead of Alpine directives. The site's
 * Content Security Policy blocks unsafe-eval, which Alpine needs to compile
 * expression strings at runtime. Keeping this interaction dependency-free
 * means the first four voices and the paging controls work under that policy.
 */
const initializeClientVoices = () => {
    document.querySelectorAll('[data-client-voices]').forEach((root) => {
        const cards = Array.from(root.querySelectorAll('[data-voice-card]'));
        const controls = root.querySelector('[data-voices-controls]');
        const previous = root.querySelector('[data-voices-prev]');
        const next = root.querySelector('[data-voices-next]');
        const pages = Array.from(root.querySelectorAll('[data-voices-page]'));
        const status = root.querySelector('[data-voices-status]');
        const perPage = 4;
        const pageCount = Math.ceil(cards.length / perPage);
        let page = 0;

        if (!controls || !previous || !next || pageCount <= 1) return;

        const render = () => {
            const start = page * perPage;
            const end = Math.min(cards.length, start + perPage);

            cards.forEach((card, index) => {
                card.hidden = index < start || index >= end;
            });
            previous.disabled = page === 0;
            next.disabled = page === pageCount - 1;
            pages.forEach((control, index) => {
                control.toggleAttribute('aria-current', index === page);
            });

            if (status) {
                status.textContent = `Showing client voices ${start + 1} to ${end} of ${cards.length}.`;
            }
        };

        previous.addEventListener('click', () => {
            if (page === 0) return;
            page -= 1;
            render();
        });
        next.addEventListener('click', () => {
            if (page === pageCount - 1) return;
            page += 1;
            render();
        });
        pages.forEach((control, index) => {
            control.addEventListener('click', () => {
                page = index;
                render();
            });
        });

        render();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeClientVoices, { once: true });
} else {
    initializeClientVoices();
}

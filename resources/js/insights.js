/**
 * The insights index "load more" feed.
 *
 * Its own entry point for the same reason as contact.js: it was 4 KB of inline
 * script inside the document, so it could not be cached apart from the HTML and
 * was re-sent with every uncached visit. The endpoint it posts to comes from
 * window.INSIGHTS_ENDPOINT, which the Blade template sets, because a Vite
 * module cannot interpolate a Blade route.
 */
document.addEventListener('alpine:init', () => {
    window.insightsFeed = function insightsFeed() {
        return {
            page: 1,
            loading: false,
            status: '',
            error: '',
            controller: null,
            init() {
                this.controller = new AbortController();
            },
            destroy() {
                // Abort any in-flight request so it cannot resolve against a
                // detached DOM node after the component is torn down.
                this.controller?.abort();
                this.controller = null;
            },
            async loadMore() {
                if (this.loading) return;
                this.loading = true;
                this.error = '';

                try {
                    const response = await fetch(`${window.INSIGHTS_ENDPOINT}?page=${this.page + 1}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            // Stated explicitly rather than relying on
                            // X-Requested-With alone to imply JSON. This is the
                            // contract that gets a readable message back on
                            // failure instead of a 500 HTML page.
                            Accept: 'application/json',
                        },
                        signal: this.controller?.signal,
                    });
                    if (!response.ok) throw new Error('Unable to load more insights.');

                    const data = await response.json();
                    const grid = document.querySelector('#insights-grid');
                    if (!grid) return;

                    // Remember where this batch starts so focus can be
                    // placed on it below.
                    const existingCount = grid.querySelectorAll('article').length;

                    grid.insertAdjacentHTML('beforeend', data.html);
                    this.page = data.next_page - 1;

                    const added = grid.querySelectorAll('article').length - existingCount;

                    if (!data.has_more) {
                        const button = this.$root.querySelector('button');
                        // Removing the button the user just pressed sends
                        // focus to <body>, which loses a keyboard user's
                        // place entirely. Hide it instead, keep it in the
                        // DOM so focus survives, and announce the change.
                        button?.setAttribute('hidden', '');
                        button?.setAttribute('aria-hidden', 'true');
                        button?.setAttribute('tabindex', '-1');
                    }

                    if (added > 0) {
                        this.status = data.has_more
                            ? `${added} more ${added === 1 ? 'insight' : 'insights'} loaded.`
                            : `All insights loaded. ${added} more ${added === 1 ? 'insight' : 'insights'} shown.`;
                    } else {
                        this.status = 'No more insights to load.';
                    }

                    // Land the caret on the first newly added heading so the
                    // next Tab continues into the new content rather than
                    // from the top of the document.
                    this.$nextTick(() => {
                        const firstNew = grid.querySelectorAll('article')[existingCount];
                        if (!firstNew) return;
                        firstNew.querySelector('h2')?.focus({ preventScroll: true });
                        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        firstNew.scrollIntoView({
                            behavior: reduced ? 'auto' : 'smooth',
                            block: 'nearest',
                        });
                    });
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    console.error(error);

                    // Two channels on purpose. `status` is sr-only, so a screen
                    // reader hears the failure but a sighted user sees nothing
                    // at all — the button just stops spinning, which reads as
                    // "there was nothing more to load". The visible notice below
                    // is what a sighted user gets, with a way to try again.
                    this.status =
                        'Sorry, more insights could not be loaded. Please try again.';
                    this.error = this.status;
                } finally {
                    this.loading = false;
                }
            },

            /**
             * Retry the page that failed. `page` was never advanced past a
             * failure, so this asks for exactly the batch that did not arrive.
             */
            retry() {
                this.loadMore();
            },

            dismiss() {
                this.error = '';
            },
        };
    };
});
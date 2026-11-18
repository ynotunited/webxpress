/**
 * Intentionally almost empty.
 *
 * This file used to import axios and assign it to window.axios. Nothing read
 * it: not any script under resources/js, not any Blade template, not any PHP.
 * Axios accounted for roughly 50 KB of the 52 KB application chunk, so every
 * page view downloaded it to never call it. The two places that do make
 * requests (the contact brief and the insights feed) use the platform fetch.
 *
 * It is kept as an import target because Filament and Livewire convention
 * assumes resources/js/app.js is the entry point, and because it is the
 * obvious place to re-add shared setup later.
 */
export {};
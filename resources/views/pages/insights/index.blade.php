<x-layouts.app
    title="Insights"
    :seoTitle="'Insights — '.site_name()"
    :seoDescription="'Practitioner writing on building, launching, and growing digital products for Nigerian business.'"
>
    @push('scripts')
        @vite('resources/js/insights.js')
    @endpush

    <div class="pt-28 pb-20" x-data="insightsFeed()">
        <div class="container-wx">
            <header class="mb-12">
                <p class="section-label mb-5"><span class="accent-bar"></span><span class="label-sm">Insights</span></p>
                <h1 class="heading-1">Insights</h1>
            </header>

            @if ($posts->isEmpty())
                <p class="body-base text-gray-500">No articles published yet.</p>
            @else
                <div id="insights-grid" class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                    @include('pages.insights._cards', ['posts' => $posts])
                </div>

                @if ($posts->hasMorePages())
                    <div class="mt-14 flex justify-center">
                        <button type="button" x-on:click="loadMore" x-bind:disabled="loading" class="inline-flex min-w-32 items-center justify-center rounded-full bg-navy px-7 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-600 disabled:cursor-wait disabled:opacity-60">
                            <span x-text="loading ? 'Loading more insights' : 'Load more'"></span>
                        </button>
                    </div>
                @endif

                {{-- Announces the batch result. Without it the removal of the
                     "Load more" button (or the silent append) left a screen
                     reader user with no confirmation that anything happened.
                     Success counts stay screen-reader-only; a failure also
                     raises the visible notice below, because sr-only means a
                     sighted user would otherwise just see the spinner stop. --}}
                <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>

                {{-- Visible failure state. role="alert" because this appears
                     without the user asking for it and they need to know why the
                     page stopped growing. x-cloak keeps it out of the document
                     until there is actually an error to report. --}}
                <div
                    x-show="error"
                    x-cloak
                    role="alert"
                    class="mx-auto mt-6 flex max-w-xl flex-col gap-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-left sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm leading-6 text-red-900">
                        <span class="font-semibold">Could not load more insights.</span>
                        This is usually a connection problem. Nothing is lost — try again.
                    </p>
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            x-on:click="retry()"
                            x-bind:disabled="loading"
                            class="rounded-full bg-navy px-5 py-2 text-xs font-semibold text-white transition-colors hover:bg-red-600 disabled:cursor-wait disabled:opacity-60"
                        >
                            <span x-text="loading ? 'Retrying' : 'Try again'"></span>
                        </button>
                        <button
                            type="button"
                            x-on:click="dismiss()"
                            class="rounded-full px-4 py-2 text-xs font-semibold text-navy ring-1 ring-gray-300 transition-colors hover:bg-white"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- The load-more feed was 4 KB of inline script in this template. It now
         lives in resources/js/insights.js as its own Vite entry, so it is
         cached across visits instead of being re-sent with the HTML. A Vite
         module cannot interpolate a Blade route, so the endpoint is handed
         over here. --}}
    <script nonce="{{ csp_nonce() }}">window.INSIGHTS_ENDPOINT = @json(route('insights.load-more'));</script>
</x-layouts.app>

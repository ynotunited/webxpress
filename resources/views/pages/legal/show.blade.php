<x-layouts.app
    :title="$title ?? $page->title ?? ''"
    :seoTitle="$seoTitle ?? $page->seo_title ?? $page->title ?? ''"
    :seoDescription="$seoDescription ?? $page->seo_description ?? ''"
>
    {{--
        The body is editor-authored rich text, so it is read through the
        model's sanitising accessor rather than echoed straight off the row.
        prose-wx styles the allowed markup to the design system; the plain
        `prose` classes this used to carry came from a typography plugin that
        is not installed, so they styled nothing.
    --}}
    @if (filled($page->headline) || filled($page->eyebrow) || filled($page->intro))
        <header class="pt-32 pb-10 md:pt-40">
            <div class="container-wx max-w-3xl">
                @if (filled($page->eyebrow))
                    <div class="section-label mb-5">
                        <span class="accent-bar"></span>
                        <span class="label-sm">{{ $page->eyebrow }}</span>
                    </div>
                @endif

                <h1 class="heading-1">{{ $page->headline ?: $page->title }}</h1>

                @if (filled($page->intro))
                    <p class="body-lg mt-6 text-gray-500">{{ $page->intro }}</p>
                @endif
            </div>
        </header>
    @endif

    <div class="{{ filled($page->headline) || filled($page->intro) ? 'pb-20' : 'pt-32 pb-20' }}">
        <div class="container-wx max-w-3xl">
            <article class="prose-wx max-w-none">
                {!! $page->bodyHtml !!}
            </article>
        </div>
    </div>
</x-layouts.app>

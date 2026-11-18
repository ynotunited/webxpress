{{--
    PAGE HERO — the one thing every page needs, editable on every page.

    A bespoke page template owns its own hero design, so it does not use this
    component; this is for a page an editor built in the admin, which has no
    bespoke template and therefore no other way to get a heading.

    Every field is optional except the H1, and the H1 falls back to the page
    title, so a page with no hero copy at all still renders a real heading
    rather than a blank band.
--}}

@props([
    'page',
])

@php
    $eyebrow = $page->eyebrow;
    $headline = $page->heading;      // falls back to title
    $intro = $page->intro;
    $heroImage = $page->hero_image_url;
@endphp

<section class="relative overflow-hidden bg-white">
    <div
        class="absolute inset-x-0 top-0 h-40 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]"
        aria-hidden="true"
    ></div>

    <div class="container-wx relative pt-32 pb-16 md:pt-40 md:pb-20">
        <div class="max-w-3xl">
            @if (filled($eyebrow))
                <div class="section-label mb-5">
                    <span class="accent-bar"></span>
                    <span class="label-sm">{{ $eyebrow }}</span>
                </div>
            @endif

            <h1 class="font-display text-[clamp(2.35rem,5vw,4.25rem)] font-bold leading-[1.06] tracking-[-0.025em] text-navy">
                {{ $headline }}
            </h1>

            @if (filled($intro))
                <p class="body-lg mt-7 max-w-2xl text-gray-500">{{ $intro }}</p>
            @endif
        </div>

        @if ($heroImage !== null)
            <div class="mt-12 overflow-hidden rounded-[1.5rem] bg-gray-50">
                <img
                    src="{{ $heroImage }}"
                    alt=""
                    class="h-full w-full object-cover"
                    width="1600"
                    height="900"
                    fetchpriority="high"
                >
            </div>
        @endif
    </div>
</section>

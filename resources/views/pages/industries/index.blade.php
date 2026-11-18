{{--
    INDUSTRIES INDEX

    There is no industries data model — the feature was never built, and this
    page was a lone <h1> on an otherwise empty body. Rather than invent sector
    claims the business has not made, the list is composed from `card_grid`
    blocks on the `industries` page record.

    That means the page is complete and useful the moment an editor fills it
    in (Admin → Site → Site Pages → industries → Add block → Card grid), is
    empty and honest until then, and needs no code change to grow. The blocks
    are the same ones the homepage uses, so the design is already proven.
--}}

@php
    use App\Support\PageBlocks;
    use App\Support\PageHero;

    $hero = PageHero::for('industries', [
        'eyebrow' => 'Industries',
        'headline' => 'Sectors we know well.',
        'intro' => 'Different industries fail in different ways. Tell us the sector you work in and we will show you what we have built for businesses in it.',
    ]);

    $industries = PageBlocks::forSlug('industries')->get('industry-cards');
    $page = \App\Models\Page::query()->where('slug', 'industries')->where('is_published', true)->first();
    $structuredCards = data_get($page?->structured_content, 'industry_cards');
    $cards = collect($structuredCards ?: $industries?->getItems() ?? [])
        ->filter(fn (array $item): bool => filled($item['name'] ?? null))
        ->values();
@endphp

<x-layouts.app
    title="Industries"
    :seoTitle="$hero['headline'] !== '' ? $hero['headline'] : 'Industries'"
    :seoDescription="Str::limit($hero['intro'], 155)"
>
    <header class="pt-32 pb-14 md:pt-40">
        <div class="container-wx max-w-3xl">
            @if (filled($hero['eyebrow']))
                <div class="section-label mb-5">
                    <span class="accent-bar"></span>
                    <span class="label-sm">{{ $hero['eyebrow'] }}</span>
                </div>
            @endif

            <h1 class="font-display text-[clamp(2.35rem,5vw,4.25rem)] font-bold leading-[1.06] tracking-[-0.025em] text-navy">
                {{ $hero['headline'] }}
            </h1>

            @if (filled($hero['intro']))
                <p class="body-lg mt-7 max-w-2xl text-gray-500">{{ $hero['intro'] }}</p>
            @endif
        </div>
    </header>

    @if ($cards->isNotEmpty())
        <section class="pb-20">
            <div class="container-wx">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cards as $card)
                        <div class="group rounded-[1.5rem] border border-gray-200 bg-white p-8 transition-colors hover:border-navy/20">
                            <h2 class="heading-3">{{ $card['name'] }}</h2>

                            @if (filled($card['description'] ?? null))
                                <p class="mt-3 text-[15px] leading-7 text-gray-500">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        {{-- No cards yet. Point the editor at the control rather than showing
             a broken-looking empty grid. --}}
        <section class="pb-24">
            <div class="container-wx">
                <div class="rounded-[1.5rem] bg-gray-50 p-10 md:p-12">
                    <h2 class="heading-3">No sectors listed yet</h2>
                    <p class="mt-3 max-w-2xl text-[15px] leading-7 text-gray-500">
                        This page is driven by a card grid block. Add one under
                        <strong>Site &rarr; Site Pages &rarr; industries &rarr; Add block &rarr; Card grid</strong>
                        and every card appears here automatically.
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-primary mt-7 rounded-full px-6">Tell us your sector</a>
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>

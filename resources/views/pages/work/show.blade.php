{{--
    PROJECT DETAIL — /work/{slug}

    Everything shown comes from App\Services\ProjectCatalog. Related projects
    are scored by shared services and category, so the suggestions stay
    relevant to the project being viewed.
--}}

<x-layouts.app
    :title="$project['name'] . ' | Webxpress Technologies'"
    :seoTitle="$project['name'] . ' — ' . $project['category'] . ' by Webxpress Technologies'"
    :seoDescription="$project['summary']"
    :seoCanonical="url()->current()"
    :ogTitle="$project['name'] . ' — Webxpress Technologies'"
    :ogDescription="$project['tagline']"
>
    {{-- =============================================
         HERO
         ============================================= --}}
    <section class="relative overflow-hidden bg-white">
        <div class="absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]" aria-hidden="true"></div>

        <div class="container-wx relative pt-10 pb-16 lg:pt-14 lg:pb-20">
            <nav class="mb-10 animate-on-scroll" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-navy">Home</a></li>
                    <li aria-hidden="true" class="text-gray-300">/</li>
                    <li><a href="{{ route('work.index') }}" class="transition-colors hover:text-navy">Work</a></li>
                    <li aria-hidden="true" class="text-gray-300">/</li>
                    <li aria-current="page" class="font-semibold text-navy">{{ $project['name'] }}</li>
                </ol>
            </nav>

            <div class="grid gap-12 lg:grid-cols-[1.08fr_0.92fr] lg:items-start">
                <div class="max-w-3xl animate-on-scroll">
                    <div class="section-label">
                        <span class="accent-bar"></span>
                        <span class="label-sm">Case study</span>
                    </div>

                    <h1 class="mt-5 font-display text-[clamp(2.35rem,5vw,4.6rem)] font-bold leading-[1.05] tracking-[-0.03em] text-navy">
                        {{ $project['name'] }}
                    </h1>

                    <p class="mt-6 font-display text-xl font-medium leading-relaxed tracking-[-0.01em] text-gray-700 lg:text-2xl">
                        {{ $project['tagline'] }}
                    </p>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-gray-600">
                        {{ $project['summary'] }}
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-6">Start a similar project</a>
                        <a href="{{ route('work.index') }}" class="btn btn-ghost rounded-full px-6">All work</a>
                        @if(!empty($project['domain']))
                            <a href="{{ $project['domain'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost rounded-full px-6">Visit live website <span aria-hidden="true">↗</span></a>
                        @endif
                    </div>
                </div>

                <div class="animate-on-scroll" data-delay="2">
                    <div class="rounded-[2rem] bg-navy p-7 text-white lg:p-8" style="background-color:#1B2D5F">
                        <p class="label-sm text-white/60">Project facts</p>

                        <dl class="mt-7 space-y-6">
                            {{-- The client is now the project name, so the row
                                 only earns its place when it says something the
                                 heading does not. --}}
                            @if(($project['client'] ?? null) && $project['client'] !== $project['name'])
                            <div class="flex items-baseline justify-between gap-6 border-b border-white/12 pb-4">
                                <dt class="text-sm text-white/60">Client</dt>
                                <dd class="text-right text-sm font-semibold text-white">{{ $project['client'] }}</dd>
                            </div>
                            @endif
                            <div class="flex items-baseline justify-between gap-6 border-b border-white/12 pb-4">
                                <dt class="text-sm text-white/60">Sector</dt>
                                <dd class="text-right text-sm font-semibold text-white">{{ $project['industry'] }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-6 border-b border-white/12 pb-4">
                                <dt class="text-sm text-white/60">Year</dt>
                                <dd class="text-right text-sm font-semibold text-white">{{ $project['year'] }}</dd>
                            </div>
                            @if(!empty($project['duration']))
                            <div class="flex items-baseline justify-between gap-6">
                                <dt class="text-sm text-white/60">Timeline</dt>
                                <dd class="text-right text-sm font-semibold text-white">{{ $project['duration'] }}</dd>
                            </div>
                            @endif
                        </dl>

                        <div class="mt-8 border-t border-white/15 pt-6">
                            <p class="text-sm text-white/60">Services used</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($services as $service)
                                    <a
                                        href="{{ route('services.show', $service['slug']) }}"
                                        class="rounded-full bg-white/[0.08] px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/15 transition-colors duration-200 hover:bg-white hover:text-navy"
                                    >{{ $service['name'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =============================================
         HERO IMAGE
         ============================================= --}}
    <section class="pb-4">
        <div class="container-wx">
            <figure class="animate-on-scroll">
                <div class="overflow-hidden rounded-[1.75rem] bg-gray-100">
                    <img
                        src="{{ $project['image'] }}"
                        alt="{{ $project['name'] }} — {{ $project['category'] }} project by Webxpress Technologies"
                        class="aspect-[16/9] w-full object-cover"
                        width="1600"
                        height="900"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
                <figcaption class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                    <span>{{ $number }} — {{ $project['category'] }}, {{ $project['year'] }}</span>
                    <span>{{ $project['industry'] }}</span>
                </figcaption>
            </figure>
        </div>
    </section>

    {{-- =============================================
         GALLERY — only when real project images exist
         ============================================= --}}
    @if(!empty($project['gallery']))
        <section class="bg-white pb-4" aria-label="{{ $project['name'] }} gallery">
            <div class="container-wx">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($project['gallery'] as $i => $image)
                        <figure class="animate-on-scroll overflow-hidden rounded-[1.5rem] bg-gray-100" data-delay="{{ min($i + 1, 4) }}">
                            <img
                                src="{{ $image }}"
                                alt="{{ $project['name'] }} — screen {{ $i + 1 }}"
                                class="{{ count($project['gallery']) > 2 && $i === 0 ? 'sm:col-span-2 ' : '' }}aspect-[16/9] w-full object-cover"
                                width="1600"
                                height="900"
                                loading="lazy"
                                decoding="async"
                            >
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- =============================================
         RESULTS
         ============================================= --}}
    @if(!empty($project['stats']))
        <section class="bg-navy py-16 text-white lg:py-20" style="background-color:#1B2D5F" aria-labelledby="results-heading">
            <div class="container-wx">
                <h2 id="results-heading" class="label-sm text-white/60">What changed after launch</h2>

                <dl class="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($project['stats'] as $stat)
                        <div class="border-l-2 border-red pl-5">
                            <dd class="font-display text-4xl font-bold leading-none tracking-[-0.03em] text-white lg:text-5xl">{{ $stat['value'] }}</dd>
                            <dt class="mt-3 text-sm leading-6 text-white/75">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>

                {{-- white/45 was 3.94:1 against the navy panel. white/70 is 7.28:1. --}}
                <p class="mt-10 text-xs leading-6 text-white/70">
                    Figures reported by the client, measured after launch against the baseline agreed at kick-off.
                </p>
            </div>
        </section>
    @endif

    {{-- =============================================
         THE SITUATION
         ============================================= --}}
    <section class="bg-white py-20 lg:py-28" aria-labelledby="situation-heading">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.75fr_1.25fr] lg:gap-16">
                <div class="animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">The situation</span></div>
                    <h2 id="situation-heading" class="font-display text-3xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-4xl">
                        The problem behind the brief
                    </h2>
                </div>

                <div class="animate-on-scroll space-y-6" data-delay="2">
                    @foreach($project['challenge'] as $paragraph)
                        <p class="text-lg leading-8 text-gray-600">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- =============================================
         THE APPROACH
         ============================================= --}}
    <section class="bg-gray-50 py-20 lg:py-28" aria-labelledby="approach-heading">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.75fr_1.25fr] lg:gap-16">
                <div class="animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">The approach</span></div>
                    <h2 id="approach-heading" class="font-display text-3xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-4xl">
                        How we built it
                    </h2>
                </div>

                <ol class="space-y-4">
                    @foreach($project['approach'] as $i => $step)
                        <li class="animate-on-scroll flex gap-5 rounded-[1.25rem] bg-white p-6 ring-1 ring-gray-200" data-delay="{{ min($i + 1, 4) }}">
                            <span class="font-display text-base font-bold tracking-widest text-red" style="color:#E31937">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <p class="text-[15px] leading-7 text-gray-700">{{ $step }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- =============================================
         DELIVERED
         ============================================= --}}
    @if(!empty($project['delivered']))
        <section class="bg-white py-20 lg:py-28" aria-labelledby="delivered-heading">
            <div class="container-wx">
                <div class="mb-12 max-w-3xl animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Scope</span></div>
                    <h2 id="delivered-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        What was delivered
                    </h2>
                    <p class="mt-5 text-lg leading-8 text-gray-600">
                        The pieces the client's team uses every day — not a highlight reel of screens.
                    </p>
                </div>

                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($project['delivered'] as $i => $item)
                        <li class="animate-on-scroll flex items-start gap-3 rounded-[1.25rem] bg-gray-50 p-5 ring-1 ring-gray-200" data-delay="{{ min($i + 1, 4) }}">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy text-white" style="background-color:#1B2D5F" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 112.83-1.42l2.79 2.8 6.79-6.8a1 1 0 011.41-.01z" clip-rule="evenodd"/></svg>
                            </span>
                            <span class="text-sm leading-7 text-gray-700">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- =============================================
         TECHNOLOGY
         ============================================= --}}
    @if(!empty($project['technology']))
        <section class="bg-white py-20 lg:py-24" aria-labelledby="technology-heading">
            <div class="container-wx">
                <div class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:gap-16">
                    <div class="animate-on-scroll">
                        <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Technology</span></div>
                        <h2 id="technology-heading" class="font-display text-3xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-4xl">
                            What it runs on
                        </h2>
                    </div>

                    <ul class="flex flex-wrap content-start gap-2.5">
                        @foreach($project['technology'] as $item)
                            <li class="animate-on-scroll rounded-full border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-navy">
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    {{-- =============================================
         OUTCOMES
         ============================================= --}}
    <section class="bg-gray-50 py-20 lg:py-28" aria-labelledby="outcomes-heading">
        <div class="container-wx">
            <div class="mb-12 max-w-3xl animate-on-scroll">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Outcomes</span></div>
                <h2 id="outcomes-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    What changed for the business
                </h2>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                @foreach($project['outcomes'] as $i => $outcome)
                    <article class="animate-on-scroll rounded-[1.5rem] bg-white p-7 ring-1 ring-gray-200" data-delay="{{ min($i + 1, 4) }}">
                        <span class="font-display text-2xl font-bold text-red" style="color:#E31937">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-4 font-display text-xl font-bold tracking-[-0.02em] text-navy">{{ $outcome['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600">{{ $outcome['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =============================================
         CLIENT QUOTE
         ============================================= --}}
    @if(!empty($project['quote']))
        <section class="bg-white py-20 lg:py-28" aria-labelledby="quote-heading">
            <div class="container-wx">
                <figure class="mx-auto max-w-4xl text-center animate-on-scroll">
                    <h2 id="quote-heading" class="sr-only">Client feedback</h2>
                    <span class="mx-auto block font-display text-6xl leading-none text-red" style="color:#E31937" aria-hidden="true">“</span>
                    <blockquote class="mt-4">
                        <p class="font-display text-2xl font-medium leading-snug tracking-[-0.02em] text-navy lg:text-4xl">
                            {{ $project['quote'] }}
                        </p>
                    </blockquote>
                    <figcaption class="mt-8 text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">
                        {{ $project['quoteAttribution'] }}
                    </figcaption>
                </figure>
            </div>
        </section>
    @endif

    {{-- =============================================
         RELATED PROJECTS
         ============================================= --}}
    @if($related !== [])
        <section class="bg-gray-50 py-20 lg:py-28" aria-labelledby="related-heading">
            <div class="container-wx">
                <div class="mb-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl animate-on-scroll">
                        <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Keep looking</span></div>
                        <h2 id="related-heading" class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                            Related projects
                        </h2>
                    </div>
                    <a href="{{ route('work.index') }}" class="shrink-0 animate-on-scroll text-sm font-semibold text-red transition-colors hover:text-red-700">
                        View all work →
                    </a>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $relatedProject)
                        <x-project-card
                            :project="$relatedProject"
                            :number="$relatedProject['number'] ?? null"
                            variant="compact"
                            :delay="$loop->iteration"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- =============================================
         CTA
         ============================================= --}}
    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="relative overflow-hidden rounded-[2rem] bg-navy px-8 py-14 text-center animate-on-scroll lg:px-16 lg:py-20" style="background-color:#1B2D5F">
                <div class="absolute inset-x-0 top-0 h-32 bg-[linear-gradient(180deg,rgba(255,255,255,0.06),rgba(255,255,255,0))]" aria-hidden="true"></div>
                <div class="relative">
                    <p class="label-sm text-white/60">Have a similar problem?</p>
                    <h2 class="mx-auto mt-4 max-w-3xl font-display text-3xl font-bold leading-tight tracking-[-0.02em] text-white lg:text-5xl">
                        Let's find the number behind your brief.
                    </h2>
                    <p class="mx-auto mt-5 max-w-xl text-lg leading-8 text-white/70">
                        Describe the situation in plain language. We will come back with scope, timeline, and an honest view of what it takes.
                    </p>
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-7">Start a project</a>
                        <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}" class="text-sm font-semibold text-white/80 underline decoration-white/30 underline-offset-4 transition-colors hover:text-white">
                            {{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

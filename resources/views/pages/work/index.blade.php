{{--
    WORK INDEX — the portfolio

    Projects come from App\Services\ProjectCatalog, the same source the homepage
    hero and Selected Work section read, so a project is described once.

    Filtering is server-side via ?category= and ?industry=, which keeps every
    filtered view linkable, crawlable, and working without JavaScript. Filter
    permutations are canonicalised to this page, not indexed separately.
--}}

<x-layouts.app
    title="Work"
    seoTitle="Work — Web, SaaS & Mobile Projects | Webxpress Technologies"
    seoDescription="Selected web, SaaS, portal, and mobile builds by Webxpress Technologies — e-commerce platforms, financial dashboards, client portals, and field service apps."
    :seoCanonical="route('work.index')"
    ogTitle="Work — Web, SaaS & Mobile Projects | Webxpress Technologies"
    ogDescription="Platforms, portals, and apps built to run on revenue, operations, and real users."
>
    @php
        $featured = $projects[0] ?? null;
        $rest = array_slice($projects, 1);
        // A two-column row that ends with a single card would look broken, so
        // the last card stretches across the row instead.
        $stretchLast = count($rest) % 2 === 1;
        $filtered = $activeCategory !== null || $activeIndustry !== null;

        $url = fn (?string $category, ?string $industry): string => route('work.index', array_filter([
            'category' => $category,
            'industry' => $industry,
        ]));

        // Two filter rows, built from one shape so the markup below stays a
        // single loop. Each row's links preserve the other row's selection, and
        // each "All" count is scoped to the other row's selection.
        $filterRows = collect([
            ['label' => 'Discipline', 'param' => 'category', 'active' => $activeCategory, 'items' => $categories],
            ['label' => 'Industry', 'param' => 'industry', 'active' => $activeIndustry, 'items' => $industries],
        ])->map(function (array $row) use ($url, $allCounts, $activeCategory, $activeIndustry): array {            $isCategory = $row['param'] === 'category';

            $row['allCount'] = $allCounts[$row['param']];
            $row['reset'] = $url($isCategory ? null : $activeCategory, $isCategory ? $activeIndustry : null);
            $row['items'] = collect($row['items'])->map(fn (array $item): array => [
                'name' => $item['name'],
                'count' => $item['count'],
                'active' => $item['slug'] === $row['active'],
                'href' => $url(
                    $isCategory ? $item['slug'] : $activeCategory,
                    $isCategory ? $activeIndustry : $item['slug']
                ),
            ])->all();

            return $row;
        })->all();

        $chipBase = 'inline-flex min-h-[44px] items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors duration-200';
        $chipOn = 'border-transparent bg-navy text-white';
        $chipOff = 'border-gray-200 bg-white text-navy hover:border-navy';
    @endphp

    {{-- =============================================
         HERO
         ============================================= --}}
    <section class="relative overflow-hidden bg-white">
        <div class="absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]" aria-hidden="true"></div>

        <div class="container-wx relative pt-12 pb-18 lg:pt-16 lg:pb-24">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
                <div class="animate-on-scroll visible">
                    {{-- Editable in the admin once a page record exists for this
                         slug; the literals are the fallback until then. --}}
                    @php($hero = \App\Support\PageHero::for('work', [
                        'eyebrow' => 'Work',
                        'headline' => 'Work that runs the business, not just the brief.',
                        'intro' => 'Every build below started as an operational problem — stock that disagreed with itself, status chased by phone call, engineers writing job cards from memory. Here is what shipped, and what changed afterwards.',
                    ]))

                    @if (filled($hero['eyebrow']))
                        <div class="section-label">
                            <span class="accent-bar"></span>
                            <span class="label-sm">{{ $hero['eyebrow'] }}</span>
                        </div>
                    @endif

                    <h1 class="max-w-4xl font-display text-[clamp(2.45rem,5.2vw,5.2rem)] font-bold leading-[1.04] tracking-[-0.03em] text-navy">
                        {{ $hero['headline'] }}
                    </h1>

                    @if (filled($hero['intro']))
                        <p class="mt-7 max-w-2xl text-lg leading-8 text-gray-600">
                            {{ $hero['intro'] }}
                        </p>
                    @endif

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-6">Discuss a project</a>
                        <a href="#projects" class="btn btn-ghost rounded-full px-6">Browse the work</a>
                    </div>
                </div>

                @if($featured)
                    <div class="animate-on-scroll" data-delay="2">
                        <div class="relative overflow-hidden rounded-[2rem] bg-navy p-6 text-white" style="background-color:#1B2D5F">
                            <div class="flex items-center justify-between gap-4">
                                <p class="label-sm text-white/60">Featured build</p>
                                <span class="label-sm text-white/70">{{ $featured['number'] }} / {{ str_pad((string) $total, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>

                            <a href="{{ route('work.show', $featured['slug']) }}" class="group mt-5 block">
                                <img
                                    src="{{ $featured['image'] }}"
                                    alt=""
                                    class="aspect-[5/3] w-full rounded-[1.4rem] object-cover opacity-80 transition-transform duration-700 ease-out group-hover:scale-[1.02]"
                                    width="900"
                                    height="540"
                                    fetchpriority="high"
                                    decoding="async"
                                >
                                <div class="mt-6 flex items-start justify-between gap-4">
                                    <div>
                                        <p class="label-sm text-white/70">{{ $featured['category'] }} · {{ $featured['year'] }}</p>
                                        <h2 class="mt-2 font-display text-2xl font-bold leading-tight tracking-[-0.02em] text-white">
                                            {{ $featured['name'] }}
                                        </h2>
                                    </div>
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 transition-colors duration-200 group-hover:bg-red group-hover:ring-transparent" aria-hidden="true">→</span>
                                </div>
                                <p class="mt-4 text-sm leading-7 text-white/70">{{ $featured['tagline'] }}</p>
                            </a>

                            <div class="mt-7 grid gap-4 border-t border-white/15 pt-6 sm:grid-cols-2">
                                <div>
                                    <p class="font-display text-3xl font-bold tracking-[-0.03em] text-white">{{ $total }}</p>
                                    <p class="mt-1.5 text-xs leading-5 text-white/60">Projects documented, with the reasoning behind each one.</p>
                                </div>
                                <div>
                                    <p class="font-display text-3xl font-bold tracking-[-0.03em] text-white">{{ now()->year - 2010 }}+</p>
                                    <p class="mt-1.5 text-xs leading-5 text-white/60">Years building web and software for African business.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- =============================================
         PROJECT GRID
         ============================================= --}}
    <section id="projects" class="scroll-mt-28 bg-gray-50 py-20 lg:py-28" aria-labelledby="projects-heading">
        <div class="container-wx">
            <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div class="max-w-2xl animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Portfolio</span></div>
                    <h2 id="projects-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        @if($filtered)
                            Filtered work
                        @else
                            Selected projects
                        @endif
                    </h2>
                    <p class="mt-5 text-lg leading-8 text-gray-600">
                        {{ count($projects) }} of {{ $total }} projects. Open any one for the problem behind it, the approach, and what changed afterwards.
                    </p>
                </div>
            </div>

            {{-- Filters. Plain links, so nothing here needs JavaScript and every
                 filtered view keeps a shareable, crawlable URL. --}}
            <div class="mt-10 flex flex-col gap-5 animate-on-scroll" data-delay="1">
                @foreach($filterRows as $row)
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-5">
                        <p class="w-28 shrink-0 pt-3 text-xs font-semibold uppercase tracking-[0.16em] text-gray-600">
                            {{ $row['label'] }}
                        </p>

                        <nav class="flex flex-wrap gap-2" aria-label="Filter projects by {{ strtolower($row['label']) }}">
                            <a
                                href="{{ $row['reset'] }}"
                                @if($row['active'] === null) aria-current="true" @endif
                                class="{{ $chipBase }} {{ $row['active'] === null ? $chipOn : $chipOff }}"
                            >
                                All
                                <span class="text-xs {{ $row['active'] === null ? 'text-white/60' : 'text-gray-600' }}">{{ $row['allCount'] }}</span>
                            </a>

                            @foreach($row['items'] as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    @if($item['active']) aria-current="true" @endif
                                    class="{{ $chipBase }} {{ $item['active'] ? $chipOn : $chipOff }}"
                                >
                                    {{ $item['name'] }}
                                    <span class="text-xs {{ $item['active'] ? 'text-white/60' : 'text-gray-600' }}">{{ $item['count'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                @endforeach
            </div>

            @if($filtered)
                <a href="{{ route('work.index') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-navy underline underline-offset-4 transition-colors hover:text-navy-600">
                    Clear filters
                    <span aria-hidden="true">→</span>
                </a>
            @endif

            @if($featured === null)
                <p class="mt-12 rounded-[1.5rem] bg-white p-8 text-base leading-8 text-gray-600 ring-1 ring-gray-200">
                    No projects match that combination. <a href="{{ route('work.index') }}" class="font-semibold text-navy underline underline-offset-4">Show all work</a> or <a href="{{ route('contact') }}" class="font-semibold text-navy underline underline-offset-4">tell us what you need built</a>.
                </p>
            @else
                <div class="mt-10">
                    {{-- Lead project: image beside copy, not another card in a row --}}
                    <a
                        href="{{ route('work.show', $featured['slug']) }}"
                        class="group animate-on-scroll block overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-gray-200 transition-shadow duration-300 hover:shadow-card-hover"
                    >
                        <div class="grid lg:grid-cols-[1.35fr_1fr]">
                            <div class="relative overflow-hidden bg-gray-100">
                                <img
                                    src="{{ $featured['image'] }}"
                                    alt=""
                                    class="aspect-[16/10] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03] lg:aspect-auto lg:h-full"
                                    width="1200"
                                    height="750"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>

                            <div class="flex flex-col justify-center bg-navy p-7 text-white lg:p-10" style="background-color:#1B2D5F">
                                <p class="label-sm text-white/55">
                                    <span class="font-mono text-white/80">{{ $featured['number'] }}</span> · {{ $featured['category'] }} · {{ $featured['year'] }}
                                </p>
                                <h3 class="mt-4 font-display text-2xl font-bold leading-tight tracking-[-0.02em] text-white lg:text-3xl">
                                    {{ $featured['name'] }}
                                </h3>
                                <p class="mt-4 text-sm leading-7 text-white/70">{{ $featured['summary'] }}</p>

                                @if(!empty($featured['stats']))
                                    <dl class="mt-7 grid gap-5 border-t border-white/15 pt-6 sm:grid-cols-2">
                                        @foreach(array_slice($featured['stats'], 0, 2) as $stat)
                                            <div>
                                                <dt class="sr-only">{{ $stat['label'] }}</dt>
                                                <dd class="font-display text-2xl font-bold tracking-[-0.02em] text-white">{{ $stat['value'] }}</dd>
                                                <dd class="mt-1 text-xs leading-5 text-white/60">{{ $stat['label'] }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif

                                <span class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-white">
                                    Read the case study
                                    <span class="transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true">→</span>
                                </span>
                            </div>
                        </div>
                    </a>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        @foreach($rest as $project)
                            <x-project-card
                                :project="$project"
                                :number="$project['number']"
                                :variant="$stretchLast && $loop->last ? 'wide' : 'standard'"
                                :delay="($loop->iteration % 2) + 1"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="mt-8 text-xs leading-6 text-gray-500">
                Figures above are results reported by clients after launch, measured against the baseline we agreed at kick-off.
            </p>
        </div>
    </section>

    {{-- =============================================
         WHAT THE WORK HAS IN COMMON
         ============================================= --}}
    <section class="bg-navy py-20 text-white lg:py-28" style="background-color:#1B2D5F" aria-labelledby="common-heading">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div class="animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Common thread</span></div>
                    <h2 id="common-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-white lg:text-5xl">
                        These are operating systems, not brochures.
                    </h2>
                    <p class="mt-6 max-w-xl text-base leading-8 text-white/70">
                        Different industries, same discipline: find the number the business actually runs on, then build the system that moves it.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach(($page?->structured_content['work_points'] ?? [
                        ['title' => 'Tied to a number', 'body' => 'Every engagement starts from a metric the client already tracks — stock accuracy, reconciliation time, response calls, collection rate.'],
                        ['title' => 'Built for real networks', 'body' => 'Designed and tested on the connections and devices customers actually use, not on office fibre.'],
                        ['title' => 'Owned by your team', 'body' => 'Code, credentials, and documentation transfer to you on final payment. Nothing stays locked inside our admin.'],
                        ['title' => 'Measured after launch', 'body' => 'We stay long enough to see whether the change held, and report what the numbers did.'],
                    ]) as $point)
                        <div class="animate-on-scroll rounded-[1.25rem] bg-white/[0.06] p-6 ring-1 ring-white/10">
                            <h3 class="font-display text-lg font-bold tracking-[-0.01em] text-white">{{ $point['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-white/68">{{ $point['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- =============================================
         CLIENT REVIEWS
         ============================================= --}}
    @if($reviews->isNotEmpty())
        <section class="bg-white py-20 lg:py-28" aria-labelledby="work-reviews-heading">
            <div class="container-wx">
                <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
                    <div class="animate-on-scroll lg:sticky lg:top-28">
                        <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Client voices</span></div>
                        <h2 id="work-reviews-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                            What clients say after the work is live
                        </h2>
                        <p class="mt-5 max-w-lg text-base leading-8 text-gray-600">
                            Feedback is tied to real projects and the outcomes they were measured against.
                        </p>
                    </div>

                    <div class="grid gap-5">
                        @foreach($reviews as $i => $review)
                            <figure class="animate-on-scroll rounded-[1.5rem] bg-gray-50 p-7 ring-1 ring-gray-200" data-delay="{{ min($i + 1, 4) }}">
                                <div class="flex gap-0.5" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                                    @for($star = 1; $star <= 5; $star++)
                                        <svg class="h-4 w-4 {{ $star <= $review->rating ? 'text-red' : 'text-gray-500' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 0 0-.364 1.118l1.287 3.957c.3.922-.756 1.688-1.539 1.118l-3.366-2.445a1 1 0 0 0-1.176 0l-3.366 2.445c-.783.57-1.838-.196-1.539-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.05 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 0 0 .95-.69l1.286-3.958z"/>
                                        </svg>
                                    @endfor
                                </div>

                                <blockquote class="mt-5">
                                    <p class="text-[15px] leading-7 text-gray-700">“{{ $review->quote }}”</p>
                                    @if($review->outcome)
                                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-navy-600">Outcome: {{ $review->outcome }}</p>
                                    @endif
                                </blockquote>

                                <figcaption class="mt-6 flex items-center justify-between gap-3 border-t border-gray-200 pt-5">
                                    <div class="min-w-0">
                                        <p class="font-display text-base font-bold text-navy">{{ $review->client_name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-gray-500">
                                            @if($review->client_role){{ $review->client_role }}@endif
                                            @if($review->company && $review->client_role) · @endif
                                            @if($review->company){{ $review->company }}@endif
                                        </p>
                                    </div>
                                    @if($review->service)
                                        <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold text-navy" style="border-color:rgba(27,45,95,0.2);color:#1B2D5F">{{ $review->service }}</span>
                                    @endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- =============================================
         CTA
         ============================================= --}}
    <section class="bg-white pb-20 lg:pb-28">
        <div class="container-wx">
            <div class="animate-on-scroll border-t border-gray-200 pt-12 lg:pt-16">
                <p class="label-sm">Your project could be the next one here</p>
                <div class="mt-4 grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-12">
                    <div>
                        <h2 class="max-w-3xl font-display text-3xl font-bold leading-tight tracking-[-0.02em] text-navy lg:text-5xl">
                            Tell us the number you need to move.
                        </h2>
                        <p class="mt-5 max-w-xl text-lg leading-8 text-gray-600">
                            Send the problem in plain language. We will tell you what it takes, what it costs, and whether we are the right team for it.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-7">Start a conversation</a>
                        <a href="{{ route('services.index') }}" class="btn btn-ghost rounded-full px-7">See our services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

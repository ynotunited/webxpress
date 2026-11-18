<x-layouts.app
    title="Services"
    seoTitle="Services | Webxpress Technologies"
    seoDescription="Explore Webxpress Technologies services across web development, SaaS platforms, mobile apps, branding, SEO, AI automation, and digital strategy."
>
    @php
        $blocks = $blocks ?? collect();
        $structured = $page?->structured_content ?? [];
        $heroImage = \App\Support\Html::assetUrl(data_get($structured, 'images.hero')) ?: asset('images/services/team-of-developers.jpg');
        $secondaryImage = \App\Support\Html::assetUrl(data_get($structured, 'images.secondary')) ?: asset('images/services/product-delivery-planning.jpg');
        $faqItems = $structured['faq'] ?? $blocks->get('faq')?->getItems() ?: [
            ['question' => 'Can we hire you for one service only?', 'answer' => 'Yes. You can bring us in for a focused website build, SEO sprint, automation workflow, AI feature, brand refresh, or strategy engagement.'],
            ['question' => 'Do you handle both design and development?', 'answer' => 'Yes. We connect product thinking, interface design, Laravel engineering, content structure, integrations, testing, and launch support.'],
            ['question' => 'How do you price service projects?', 'answer' => 'Pricing depends on scope, complexity, integrations, timeline, content readiness, and support needs. We confirm this after discovery instead of guessing.'],
            ['question' => 'Can you improve an existing website or app?', 'answer' => 'Yes. We can audit what exists, identify the weak points, and run a focused improvement sprint without rebuilding everything.'],
            ['question' => 'What happens after launch?', 'answer' => 'We can hand over cleanly, provide support, continue with improvements, or help your internal team maintain the system with confidence.'],
        ];
        $services = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'summary' => 'High-performance websites, Laravel applications, portals, dashboards, and digital platforms built around real business workflows.',
                'fit' => 'Best for companies that need a serious web presence connected to operations.',
            ],
            [
                'name' => 'Mobile Development',
                'slug' => 'mobile-development',
                'summary' => 'Cross-platform and product-ready mobile apps with clean journeys, reliable backend integration, and launch support.',
                'fit' => 'Best for teams bringing a service, marketplace, or internal workflow to phones.',
            ],
            [
                'name' => 'Branding',
                'slug' => 'branding',
                'summary' => 'Identity systems, positioning, interface-ready visual rules, and launch assets that make the product feel coherent.',
                'fit' => 'Best for new businesses or established brands ready for a sharper market signal.',
            ],
            [
                'name' => 'Digital Strategy',
                'slug' => 'digital-strategy',
                'summary' => 'Roadmaps, content structure, technical planning, analytics thinking, and product decisions before major build work begins.',
                'fit' => 'Best when the goal is clear but the right digital path still needs shaping.',
            ],
            [
                'name' => 'Visuals',
                'slug' => 'visuals',
                'summary' => 'Art direction, interface visuals, campaign assets, and production guidance for polished digital presentation.',
                'fit' => 'Best for teams that need their product or brand to feel more credible on sight.',
            ],
            [
                'name' => 'Motion Design',
                'slug' => 'motion-design',
                'summary' => 'Purposeful animation, product explainers, interface motion, and video assets that improve clarity and recall.',
                'fit' => 'Best when a static page cannot explain the value fast enough.',
            ],
            [
                'name' => 'SEO',
                'slug' => 'seo',
                'summary' => 'Technical SEO, content architecture, page performance, structured metadata, and search-focused publishing foundations.',
                'fit' => 'Best for organizations that want durable visibility instead of short campaign spikes.',
            ],
            [
                'name' => 'AI Solutions',
                'slug' => 'ai-solutions',
                'summary' => 'AI-assisted workflows, internal automations, response systems, document handling, and intelligent product features.',
                'fit' => 'Best for teams with repetitive work that can be made faster and more consistent.',
            ],
        ];

        $featured = $services[0];
    @endphp

    <section class="relative overflow-hidden bg-white">
        <div class="absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]" aria-hidden="true"></div>

        <div class="container-wx relative pt-12 pb-18 lg:pt-16 lg:pb-24">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
                <div class="animate-on-scroll visible">
                    {{-- Editable in the admin once a page record exists for this
                         slug; the literals are the fallback until then. --}}
                    @php($hero = \App\Support\PageHero::for('services', [
                        'eyebrow' => 'Services',
                        'headline' => 'Digital services for teams that need more than a good-looking website.',
                        'intro' => 'We plan, design, build, and improve the systems that sit behind modern businesses: websites, portals, apps, automations, search foundations, and product workflows.',
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
                        <a href="#service-stack" class="btn btn-ghost rounded-full px-6">Explore services</a>
                    </div>
                </div>

                <div class="animate-on-scroll" data-delay="2">
                    <div class="relative overflow-hidden rounded-[2rem] bg-navy p-6 text-white" style="background-color:#1B2D5F">
                        {{-- LCP candidate for this page, so it is eager and prioritised. width/height
                         reserve the box so the reveal does not shift layout. --}}
                        <img
                            src="{{ $heroImage }}"
                            alt="Digital product planning workspace"
                            class="aspect-[5/3] w-full rounded-[1.4rem] object-cover opacity-78 mix-blend-luminosity"
                            width="900"
                            height="760"
                            fetchpriority="high"
                            decoding="async"
                        >
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="font-display text-4xl font-bold tracking-[-0.03em]">8</p>
                                <p class="mt-2 text-sm leading-6 text-white/68">Service areas that connect strategy, design, engineering, and growth.</p>
                            </div>
                            <div class="rounded-[1.25rem] bg-white/[0.08] p-5 ring-1 ring-white/10">
                                <p class="text-sm font-semibold text-white">Built as one system</p>
                                <p class="mt-2 text-sm leading-6 text-white/68">Your site, admin tools, forms, payments, content, and automation should speak to each other.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="service-stack" class="bg-gray-50 py-20 lg:py-28">
        <div class="container-wx">
            <div class="mb-12 max-w-3xl animate-on-scroll">
                <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    Service stack
                </h2>
                <p class="mt-5 text-lg leading-8 text-gray-600">
                    Pick one service or combine several into a single delivery plan. Each area is built to connect with the others.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($services as $index => $service)
                    <article class="group animate-on-scroll rounded-[1.5rem] bg-white p-6 ring-1 ring-gray-200 transition-all duration-300 hover:-translate-y-1 hover:ring-navy/20" data-delay="{{ min($index + 1, 4) }}">
                        <a href="{{ route('services.show', $service['slug']) }}" class="flex h-full flex-col">
                            <div class="flex items-start justify-between gap-4">
                                <p class="font-display text-2xl font-bold leading-tight tracking-[-0.02em] text-navy">{{ $service['name'] }}</p>
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gray-200 text-base text-navy transition-colors duration-200 group-hover:border-red-600 group-hover:bg-red-600 group-hover:text-white" aria-hidden="true">→</span>
                            </div>
                            <p class="mt-5 text-sm leading-7 text-gray-600">{{ $service['summary'] }}</p>
                            <p class="mt-auto pt-7 text-xs font-semibold uppercase tracking-[0.16em] text-gray-600">View service</p>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="max-w-4xl px-1 animate-on-scroll sm:px-4 lg:px-8">
                <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    Choose the engagement shape that matches the problem.
                </h2>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-gray-600">
                    Not every project needs the same operating model. We keep the structure clear so you know what you are buying and how the work will move.
                </p>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                @foreach ([
                    ['title' => 'Launch Build', 'body' => 'A focused website, portal, or product build with defined scope, milestones, testing, deployment, and handover.', 'items' => ['Discovery', 'UX and UI', 'Development', 'Launch support']],
                    ['title' => 'Growth Sprint', 'body' => 'A short, sharp improvement cycle for performance, SEO, conversion paths, analytics, content structure, or automation.', 'items' => ['Audit', 'Prioritized fixes', 'Implementation', 'Reporting']],
                    ['title' => 'Product Partner', 'body' => 'Ongoing design and engineering support for teams that need a steady partner across product improvements.', 'items' => ['Roadmap support', 'Feature delivery', 'Maintenance', 'Optimization']],
                ] as $model)
                    <article class="animate-on-scroll rounded-[1.5rem] bg-gray-50 p-7 ring-1 ring-gray-200">
                        <h3 class="font-display text-2xl font-bold tracking-[-0.02em] text-navy">{{ $model['title'] }}</h3>
                        <p class="mt-5 text-sm leading-7 text-gray-600">{{ $model['body'] }}</p>
                        <div class="mt-7 flex flex-wrap gap-2">
                            @foreach ($model['items'] as $item)
                                <span class="rounded-full bg-white px-3 py-2 text-xs font-semibold text-navy ring-1 ring-gray-200">{{ $item }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-navy py-20 text-white lg:py-28" style="background-color:#1B2D5F">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div class="animate-on-scroll">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/55">What clients get</p>
                    <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-white lg:text-5xl">
                        Useful outcomes, not a pile of disconnected deliverables.
                    </h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'A clearer customer journey from first visit to inquiry.',
                        'Admin tools that reduce manual follow-up and repeated work.',
                        'A technical foundation that can survive iteration.',
                        'A content and SEO structure that supports long-term visibility.',
                        'Design decisions that make the business easier to understand.',
                        'Launch support with practical handover and next-step guidance.',
                    ] as $outcome)
                        <div class="animate-on-scroll rounded-[1.25rem] bg-white/[0.06] p-5 text-sm leading-7 text-white/72 ring-1 ring-white/10">
                            {{ $outcome }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if($reviews->isNotEmpty())
    <section
        class="bg-white py-20 lg:py-28"
        aria-labelledby="reviews-heading"
        aria-roledescription="carousel"
        x-data="{
            index: 0,
            pages: 1,
            timer: null,
            paused: false,
            controller: null,
            get reducedMotion() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; },
            init() {
                this.updatePages();
                this._onResize = () => this.updatePages();
                window.addEventListener('resize', this._onResize, { passive: true });
                if (this.pages > 1) this.start();
            },
            destroy() {
                this.stop();
                window.removeEventListener('resize', this._onResize);
                this._onResize = null;
            },
            perView() {
                const track = this.$refs.track;
                const first = track.firstElementChild;
                return first ? Math.max(1, Math.round(track.clientWidth / first.offsetWidth)) : 1;
            },
            updatePages() {
                const per = this.perView();
                this.pages = Math.max(1, Math.ceil((this.$refs.track.children.length || 0) / per));
                this.index = Math.min(this.index, this.pages - 1);
            },
            start() {
                this.stop();
                if (this.paused || this.reducedMotion || this.pages <= 1) return;
                this.timer = setInterval(() => this.next(), 5000);
            },
            toggleTimer() { this.paused = !this.paused; this.paused ? this.stop() : this.start(); },
            stop() {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },
            next() { this.index = (this.index + 1) % this.pages; this.scroll(); },
            prev() { this.index = (this.index - 1 + this.pages) % this.pages; this.scroll(); },
            go(i) { this.index = i; this.scroll(); },
            scroll() {
                const track = this.$refs.track;
                const first = track.firstElementChild;
                const step = (first ? first.offsetWidth : 0) * this.perView();
                track.scrollTo({ left: this.index * step, behavior: this.reducedMotion ? 'auto' : 'smooth' });
                this.start();
            },
            syncFromScroll() {
                const track = this.$refs.track;
                const first = track.firstElementChild;
                const step = (first ? first.offsetWidth : 0) * this.perView();
                if (!step) return;
                this.index = Math.max(0, Math.min(Math.round(track.scrollLeft / step), this.pages - 1));
            }
        }"
    >
        <div class="container-wx">
            <div class="mb-12">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Client Voices</span></div>
                <h2 id="reviews-heading" class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    Client reviews
                </h2>
                <p class="mt-6 max-w-xl text-lg leading-8 text-gray-600">
                    Verified client feedback tied to real projects, service context, and business outcomes.
                </p>
            </div>

            <div
                x-ref="track"
                id="reviews-track"
                class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                style="scrollbar-width:none;-ms-overflow-style:none"
                tabindex="0"
                role="group"
                aria-label="Client reviews, scrollable"
                @scroll.passive="syncFromScroll()"
                @mouseenter="stop()"
                @mouseleave="start()"
                @focusin="stop()"
                @focusout="start()"
            >
                @foreach($reviews as $i => $review)
                    <figure class="animate-on-scroll flex w-full flex-col rounded-[1.5rem] bg-gray-50 p-7 ring-1 ring-gray-200 snap-start shrink-0 px-2.5 md:w-1/2 lg:w-1/3" data-delay="{{ min($i+1, 4) }}">
                        <blockquote class="flex-1">
                            <div class="flex gap-0.5 mb-4" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                                @for($star = 1; $star <= 5; $star++)
                                    <svg class="w-4 h-4 {{ $star <= $review->rating ? 'text-red' : 'text-gray-600' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 0 0-.364 1.118l1.287 3.957c.3.922-.756 1.688-1.539 1.118l-3.366-2.445a1 1 0 0 0-1.176 0l-3.366 2.445c-.783.57-1.838-.196-1.539-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.05 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 0 0 .95-.69l1.286-3.958z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-[15px] leading-7 text-gray-700">“{{ $review->quote }}”</p>
                            @if($review->outcome)
                                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-navy">Outcome: {{ $review->outcome }}</p>
                            @endif
                        </blockquote>

                        <figcaption class="mt-6 pt-5 border-t border-gray-200">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-display text-base font-bold text-navy">{{ $review->client_name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                                        @if($review->client_role){{ $review->client_role }}@endif
                                        @if($review->company && $review->client_role) · @endif
                                        @if($review->company){{ $review->company }}@endif
                                    </p>
                                </div>
                                @if($review->service)
                                    <span class="shrink-0 rounded-full border border-navy/20 px-2.5 py-0.5 text-[11px] font-semibold text-navy" style="border-color:rgba(27,45,95,0.2);color:#1B2D5F">{{ $review->service }}</span>
                                @endif
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            {{-- Carousel controls — centred beneath the reviews --}}
            <div class="mt-10 flex items-center justify-center gap-4" x-show="pages > 1" x-cloak>
                <button
                    type="button"
                    class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-navy transition-colors duration-200 hover:border-red-600 hover:bg-red-600 hover:text-white bg-white"
                    @click="prev()"
                    aria-label="Previous reviews"
                    aria-controls="reviews-track"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </button>

                <p class="sr-only" role="status" aria-live="polite" x-text="'Showing reviews page ' + (index + 1) + ' of ' + pages"></p>

                <div class="flex items-center">
                    <template x-for="(p, dotIndex) in Array.from({ length: pages })" :key="dotIndex">
                        <button
                            type="button"
                            class="-m-3 p-3 rounded-full"
                            :aria-label="'Go to reviews page ' + (dotIndex + 1)"
                            :aria-current="dotIndex === index ? 'true' : 'false'"
                            @click="go(dotIndex)"
                        >
                            <span class="block h-2 rounded-full transition-all duration-300" :class="dotIndex === index ? 'w-6 bg-red' : 'w-2 bg-gray-400'"></span>
                        </button>
                    </template>
                </div>

                <button
                    type="button"
                    class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-navy transition-colors duration-200 hover:border-red-600 hover:bg-red-600 hover:text-white bg-white"
                    @click="next()"
                    aria-label="Next reviews"
                    aria-controls="reviews-track"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </button>

                <button
                    type="button"
                    class="inline-flex h-12 min-w-12 items-center justify-center gap-2 rounded-full border border-gray-200 px-3 text-xs font-semibold text-navy transition-colors duration-200 hover:border-red-600 hover:text-red-600 bg-white"
                    @click="toggleTimer()"
                    :aria-label="paused ? 'Resume review carousel' : 'Pause review carousel'"
                    :aria-pressed="paused"
                >
                    <span aria-hidden="true" x-text="paused ? '▶ Play' : '❚❚ Pause'"></span>
                </button>
            </div>
        </div>
    </section>
    @endif

    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="grid gap-12 lg:grid-cols-[1fr_1fr] lg:items-center">
                <div class="animate-on-scroll">
                    {{-- Second half of the page, so it must not compete with the hero image
                         for bandwidth. width/height already reserve the box. --}}
                    <img
                        src="{{ $secondaryImage }}"
                        alt="Team reviewing a digital service plan"
                        class="aspect-[4/3] w-full rounded-[2rem] object-cover"
                        width="900"
                        height="860"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="animate-on-scroll" data-delay="2">
                    <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        We start with the operational truth behind the request.
                    </h2>
                    <div class="mt-8 space-y-6 text-base leading-8 text-gray-600">
                        <p>
                            A website request may really be a sales pipeline problem. An app request may really be a workflow problem. An SEO request may really be a content structure problem.
                        </p>
                        <p>
                            We look for the business system underneath the brief, then build the smallest strong solution that can grow without becoming fragile.
                        </p>
                    </div>
                    <a href="{{ route('contact') }}" class="btn btn-primary mt-8 rounded-full px-7">Book a consultation</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-50 py-20 lg:py-28">
        <div class="container-wx">
            <div class="mx-auto max-w-3xl text-center animate-on-scroll">
                <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    Questions before we start
                </h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                    A few answers that help teams understand how we scope, build, launch, and support service work.
                </p>
            </div>

            <div class="mx-auto mt-12 max-w-4xl space-y-4">
                @foreach ($faqItems as $faq)
                    <details class="group animate-on-scroll rounded-[1.25rem] bg-white p-6 ring-1 ring-gray-200">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6">
                            <span class="font-display text-xl font-bold tracking-[-0.02em] text-navy">{{ $faq['question'] }}</span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-50 text-xl text-navy transition-transform duration-200 group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-gray-600">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="overflow-hidden rounded-[2rem] bg-white ring-1 ring-gray-200">
                <div class="grid gap-8 p-8 sm:p-10 lg:grid-cols-[1.1fr_0.9fr] lg:p-14">
                    <div class="animate-on-scroll">
                        <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                            Have a project that touches design, code, content, and operations?
                        </h2>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-gray-600">
                            Tell us what you are trying to make easier, faster, clearer, or more profitable. We will help shape the right service mix.
                        </p>
                    </div>
                    <div class="flex items-end lg:justify-end">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-7">Start a project</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

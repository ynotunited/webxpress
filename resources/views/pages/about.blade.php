<x-layouts.app
    title="About Webxpress Technologies"
    :seoTitle="$seoTitle ?? 'About Webxpress Technologies'"
    :seoDescription="$seoDescription ?? 'Meet Webxpress Technologies, a Nigerian digital product studio building websites, SaaS platforms, portals, and business systems for ambitious organizations.'"
>
    @php
        $blocks = $blocks ?? collect();
        $structured = $page?->structured_content ?? [];
        $heroImage = \App\Support\Html::assetUrl(data_get($structured, 'images.hero')) ?: asset('images/about/hero-product-planning.jpg');
        $secondaryImage = \App\Support\Html::assetUrl(data_get($structured, 'images.secondary')) ?: asset('images/about/engineering-review.jpg');
        // Pre-cropped to the 3:4 ratio the template displays below, so none
        // of the bytes are for pixels object-cover would discard. The source
        // PNG of this image is 2.6 MB; the WebP is 74 KB.
        $workshopImage = asset('images/about/product-workshop.webp');
        $workshopImage = \App\Support\Html::assetUrl(data_get($structured, 'images.tertiary')) ?: $workshopImage;
        if ($secondaryImage === $heroImage) {
            $secondaryImage = asset('images/about/engineering-review.jpg');
        }
        if (in_array($workshopImage, [$heroImage, $secondaryImage], true)) {
            $workshopImage = asset('images/about/product-workshop.webp');
        }
        $principles = $structured['principles'] ?? $blocks->get('principles')?->getItems() ?: [
            ['title' => 'Clarity before code', 'body' => 'We shape the business problem first, then choose the simplest technical path that can scale.'],
            ['title' => 'Systems over pages', 'body' => 'A website, CRM, payment flow, and admin panel should feel like one connected machine.'],
            ['title' => 'Design with restraint', 'body' => 'Interfaces should guide decisions, reduce friction, and make important actions obvious.'],
            ['title' => 'Launch is a checkpoint', 'body' => 'We build with maintenance, iteration, SEO, analytics, and future product moves in mind.'],
        ];
        $processSteps = $structured['process'] ?? $blocks->get('process')?->getItems() ?: [
            ['title' => 'Discover', 'body' => 'We map the business goal, users, risks, integrations, and launch constraints.'],
            ['title' => 'Shape', 'body' => 'We turn the brief into content structure, UX flows, technical scope, and delivery milestones.'],
            ['title' => 'Build', 'body' => 'Design and engineering move together so the interface and system logic stay aligned.'],
            ['title' => 'Launch', 'body' => 'We test, deploy, hand over, and help the team understand how to run the system.'],
        ];
    @endphp
    <section class="relative overflow-hidden bg-white">
        <div class="absolute inset-x-0 top-0 h-40 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]" aria-hidden="true"></div>

        <div class="container-wx relative pt-12 pb-20 lg:pt-16 lg:pb-28">
            <div class="grid gap-12 lg:grid-cols-[1.08fr_0.92fr] lg:items-end">
                <div class="max-w-3xl animate-on-scroll visible">
                    {{--
                        Hero copy comes from the page record when one exists, so
                        the opening statement of the page is editable in the
                        admin. The literals below are the fallback and are what
                        renders until a page record is created for this slug.
                    --}}
                    @php($hero = \App\Support\PageHero::for('about', [
                        'eyebrow' => 'About Webxpress',
                        'headline' => 'We build the digital backbone for ambitious teams.',
                        'intro' => 'Webxpress Technologies designs and engineers websites, SaaS platforms, client portals, and business systems that help organizations sell, operate, and scale with confidence.',
                    ]))

                    @if (filled($hero['eyebrow']))
                        <div class="section-label">
                            <span class="accent-bar"></span>
                            <span class="label-sm">{{ $hero['eyebrow'] }}</span>
                        </div>
                    @endif

                    <h1 class="font-display text-[clamp(2.35rem,5vw,4.85rem)] font-bold leading-[1.06] tracking-[-0.025em] text-navy">
                        {{ $hero['headline'] }}
                    </h1>

                    @if (filled($hero['intro']))
                        <p class="mt-7 max-w-2xl text-lg leading-8 text-gray-600">
                            {{ $hero['intro'] }}
                        </p>
                    @endif

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-6">Start a project</a>
                        <a href="{{ route('work.index') }}" class="btn btn-ghost rounded-full px-6">See our work</a>
                    </div>
                </div>

                <div class="animate-on-scroll" data-delay="2">
                    <div class="relative">
                        <div class="aspect-[4/5] overflow-hidden rounded-[2rem] bg-navy">
                            <img
                                src="{{ $heroImage }}"
                                alt="A modern workspace with product strategy and engineering activity"
                                class="h-full w-full object-cover opacity-90 mix-blend-luminosity"
                                width="900"
                                height="1125"
                                fetchpriority="high"
                                decoding="async"
                            >
                        </div>

                        <div class="absolute -bottom-8 left-5 right-5 rounded-[1.25rem] bg-white p-5 shadow-[0_24px_70px_-36px_rgba(15,26,53,0.45)] ring-1 ring-navy/10 sm:left-auto sm:right-8 sm:w-72">
                            <p class="text-sm font-semibold text-navy">Founded on delivery</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                We pair strategy, interface design, Laravel engineering, and automation so clients get complete systems, not loose screens.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-50 py-16 lg:py-24">
        <div class="container-wx">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['value' => '15+', 'label' => 'Years building for the web'],
                    ['value' => '200+', 'label' => 'Projects shipped across sectors'],
                    ['value' => '4', 'label' => 'Core disciplines in one team'],
                    ['value' => '1', 'label' => 'Accountable partner from brief to launch'],
                ] as $stat)
                    <div class="animate-on-scroll rounded-[1.25rem] bg-white p-6 ring-1 ring-gray-200">
                        <p class="font-display text-4xl font-bold tracking-[-0.03em] text-navy">{{ $stat['value'] }}</p>
                        <p class="mt-3 text-sm leading-6 text-gray-600">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="mx-auto max-w-4xl text-center animate-on-scroll">
                <h2 class="font-display text-[clamp(2rem,5vw,4.4rem)] font-bold leading-[1.04] tracking-[-0.03em] text-navy">
                    We are not here to decorate the internet. We are here to make businesses work better.
                </h2>
                <p class="mx-auto mt-7 max-w-2xl text-lg leading-8 text-gray-600">
                    Our best projects sit close to revenue, operations, customer support, and internal workflows. That is where thoughtful software earns its keep.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-navy py-20 text-white lg:py-28" style="background-color:#1B2D5F">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div class="animate-on-scroll">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/55">How we think</p>
                    <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-white lg:text-5xl">
                        Practical craft, sharp strategy, calm execution.
                    </h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($principles as $principle)
                        <article class="animate-on-scroll rounded-[1.25rem] bg-white/[0.06] p-6 ring-1 ring-white/10 transition-transform duration-300 hover:-translate-y-1">
                            <h3 class="font-display text-xl font-bold text-white">{{ $principle['title'] }}</h3>
                            <p class="mt-4 text-sm leading-7 text-white/68">{{ $principle['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="grid gap-12 lg:grid-cols-[1fr_1.05fr] lg:items-center">
                <div class="order-2 animate-on-scroll lg:order-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4 pt-10">
                            {{-- Both are well below the fold, so they must not compete with the hero
                                 for bandwidth. width/height already reserve the
                                 aspect-ratio box, so lazy loading costs no
                                 layout shift. --}}
                            <img
                                src="{{ $secondaryImage }}"
                                alt="Developer desk with a laptop and planning notes"
                                class="aspect-[3/4] w-full rounded-[1.5rem] object-cover"
                                width="520"
                                height="680"
                                loading="lazy"
                                decoding="async"
                            >
                            <div class="rounded-[1.5rem] bg-red-600 p-6 text-white">
                                <p class="font-display text-3xl font-bold tracking-[-0.03em]">Build</p>
                                <p class="mt-3 text-sm leading-6 text-white/80">Production-ready foundations, not disposable prototypes.</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-[1.5rem] bg-gray-100 p-6">
                                <p class="font-display text-3xl font-bold tracking-[-0.03em] text-navy">Think</p>
                                <p class="mt-3 text-sm leading-6 text-gray-600">Strategy, content structure, user flows, and operational logic.</p>
                            </div>
                            <img
                                src="{{ $workshopImage }}"
                                alt="Team planning a digital product workflow"
                                class="aspect-[3/4] w-full rounded-[1.5rem] object-cover"
                                width="520"
                                height="760"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                    </div>
                </div>

                <div class="order-1 animate-on-scroll lg:order-2" data-delay="2">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-red-600">What makes us different</p>
                    <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        We bridge the gap between a brand website and the software behind it.
                    </h2>
                    <div class="mt-8 space-y-6 text-base leading-8 text-gray-600">
                        <p>
                            Many teams outgrow simple brochure websites. They need portals, dashboards, payment journeys, intake forms, admin workflows, content systems, and support processes.
                        </p>
                        <p>
                            That is where Webxpress fits. We understand how polished frontend experiences connect to durable backend architecture, automation, and measurable business outcomes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-50 py-20 lg:py-28">
        <div class="container-wx">
            <div class="max-w-3xl animate-on-scroll">
                <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    A clear path from first conversation to launch.
                </h2>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                    Modern work needs rhythm. We keep projects moving with visible decisions, focused scopes, and communication that respects everyone’s time.
                </p>
            </div>

            <div class="mt-12 grid gap-4 lg:grid-cols-4">
                @foreach ($processSteps as $step)
                    <article class="animate-on-scroll rounded-[1.25rem] bg-white p-6 ring-1 ring-gray-200">
                        <h3 class="font-display text-2xl font-bold tracking-[-0.02em] text-navy">{{ $step['title'] }}</h3>
                        <p class="mt-5 text-sm leading-7 text-gray-600">{{ $step['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="overflow-hidden rounded-[2rem] bg-navy text-white" style="background-color:#1B2D5F">
                <div class="grid gap-10 p-8 sm:p-10 lg:grid-cols-[1.1fr_0.9fr] lg:p-14">
                    <div class="animate-on-scroll">
                        <h2 class="font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-white lg:text-5xl">
                            If your business has moved past a basic website, we should talk.
                        </h2>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-white/72">
                            Bring us the messy version: the idea, the workflow, the revenue goal, the spreadsheet, the old system, or the process that keeps slowing your team down.
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

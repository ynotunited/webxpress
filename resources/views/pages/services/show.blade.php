<x-layouts.app
    :title="$service['name'] . ' | Webxpress Technologies'"
    :seoTitle="$service['name'] . ' — Webxpress Technologies'"
    :seoDescription="$service['summary']"
>
    @php
        $structured = $page?->structured_content ?? [];
        $service['deliverables'] = $structured['deliverables'] ?? $service['deliverables'];
        $service['outcomes'] = $structured['outcomes'] ?? $service['outcomes'];
        if (filled($structured['faq'] ?? null)) {
            $service['faq'] = collect($structured['faq'])->map(fn (array $item): array => [
                'q' => $item['question'] ?? '',
                'a' => $item['answer'] ?? '',
            ])->all();
        }
        $process = [
            ['step' => '01', 'title' => 'Scope', 'body' => 'One working session to pin down the outcome, the constraints, and what "done" looks like — before anything is designed or built.'],
            ['step' => '02', 'title' => 'Plan', 'body' => 'A written schedule and a fixed quote. You approve the plan, the cost, and the phases before we spend a single hour.'],
            ['step' => '03', 'title' => 'Design', 'body' => 'Concepts, an honest review loop, and iteration until the direction is right — never a design you meet for the first time at launch.'],
            ['step' => '04', 'title' => 'Build', 'body' => 'Working in set phases with regular progress notes and review checkpoints. Nothing is hidden, and nothing ships unannounced.'],
            ['step' => '05', 'title' => 'Launch & support', 'body' => 'Deployment, training, and a clean handover — then we stay close through the first weeks of real use.'],
        ];
    @endphp

    {{-- HERO --}}
    <section class="relative overflow-hidden bg-white">
        <div class="absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(27,45,95,0.08),rgba(255,255,255,0))]" aria-hidden="true"></div>

        <div class="container-wx relative pt-10 pb-20 lg:pt-14 lg:pb-28">
            <nav class="mb-10 animate-on-scroll" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                    <li><a href="{{ route('home') }}" class="hover:text-navy-800 transition-colors">Home</a></li>
                    <li aria-hidden="true" class="text-gray-300">/</li>
                    <li><a href="{{ route('services.index') }}" class="hover:text-navy-800 transition-colors">Services</a></li>
                    <li aria-hidden="true" class="text-gray-300">/</li>
                    {{-- aria-current marks the trail's end. Without it the breadcrumb read as
                         three undifferentiated items and nothing said "you are here". --}}
                    <li aria-current="page" class="font-semibold text-navy" style="color:#1B2D5F">{{ $service['name'] }}</li>
                </ol>
            </nav>

            <div class="grid gap-12 lg:grid-cols-[1.08fr_0.92fr] lg:items-start">
                <div class="max-w-3xl animate-on-scroll">
                    <div class="section-label">
                        <span class="accent-bar"></span>
                        <span class="label-sm">Service</span>
                    </div>

                    <h1 class="mt-5 font-display text-[clamp(2.35rem,5vw,4.85rem)] font-bold leading-[1.05] tracking-[-0.025em] text-navy">
                        {{ $service['name'] }}
                    </h1>

                    <p class="mt-6 font-display text-xl font-medium leading-relaxed tracking-[-0.01em] text-gray-700">
                        {{ $service['tagline'] }}
                    </p>

                    <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600">
                        {{ $service['longSummary'] }}
                    </p>

                    <p class="mt-6 max-w-xl text-sm leading-relaxed text-gray-500">
                        <span class="font-semibold uppercase tracking-wider text-navy-600">Who it's for —</span> {{ $service['fit'] }}
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-6">Start a project</a>
                        <a href="{{ route('work.index') }}" class="btn btn-ghost rounded-full px-6">See our work</a>
                    </div>
                </div>

                <div class="lg:sticky lg:top-24">
                    <div class="rounded-[2rem] bg-navy p-8 text-white lg:p-10 animate-on-scroll" data-delay="2" style="background-color:#1B2D5F">
                        <p class="label-sm text-white/60">What this service covers</p>
                        <ol class="mt-7 space-y-6">
                            @foreach(array_slice($service['deliverables'], 0, 3) as $i => $item)
                                <li class="flex gap-4">
                                    <span class="font-display text-lg font-bold text-red" style="color:#E31937">0{{ $i + 1 }}</span>
                                    <div>
                                        <p class="font-semibold text-white">{{ $item['title'] }}</p>
                                        <p class="mt-1.5 text-sm leading-6 text-white/75">{{ $item['description'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        <div class="mt-8 border-t border-white/15 pt-6 flex items-center justify-between">
                            <span class="text-sm text-white/70">Deliverables</span>
                            <span class="font-display text-2xl font-bold text-white">{{ count($service['deliverables']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- DELIVERABLES --}}
    <section class="bg-gray-50 py-20 lg:py-28">
        <div class="container-wx">
            <div class="mb-14 max-w-3xl animate-on-scroll">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">What you get</span></div>
                <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    Everything {{ strtolower($service['name']) }} includes
                </h2>
                <p class="mt-5 text-lg leading-8 text-gray-600">
                    Scoped clearly before sign-off, so the deliverable list matches the quote — and the quote matches reality.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($service['deliverables'] as $i => $item)
                    <article class="group animate-on-scroll rounded-[1.5rem] bg-white p-7 ring-1 ring-gray-200 transition-all duration-300 hover:-translate-y-1 hover:ring-navy/20" data-delay="{{ min($i + 1, 4) }}">
                        <span class="font-display text-sm font-bold tracking-widest text-red" style="color:#E31937">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-4 font-display text-xl font-bold tracking-[-0.01em] text-navy">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-gray-600">{{ $item['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PROCESS --}}
    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="mb-14 max-w-3xl animate-on-scroll">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">How we work</span></div>
                <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                    The same operating rhythm for every project
                </h2>
                <p class="mt-5 text-lg leading-8 text-gray-600">
                    A fixed process that removes surprises — for {{ strtolower($service['name']) }}, every phase is gated by your approval.
                </p>
            </div>

            <ol class="grid gap-5 md:grid-cols-2 lg:grid-cols-5">
                @foreach($process as $i => $phase)
                    <li class="relative animate-on-scroll rounded-[1.5rem] bg-gray-50 p-6 ring-1 ring-gray-200" data-delay="{{ min($i + 1, 4) }}">
                        @if(!$loop->last)
                            <span class="hidden lg:block absolute top-10 -right-5 z-10" aria-hidden="true">
                                <svg class="w-4 h-4 text-gray-300" viewBox="0 0 20 20" fill="currentColor"><path d="M8 5l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        @endif
                        <span class="font-display text-sm font-bold tracking-widest text-red" style="color:#E31937">{{ $phase['step'] }}</span>
                        <h3 class="mt-3 font-display text-lg font-bold text-navy">{{ $phase['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-gray-600">{{ $phase['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- OUTCOMES --}}
    <section class="bg-navy py-20 text-white lg:py-28" style="background-color:#1B2D5F">
        <div class="container-wx">
            <div class="mb-14 max-w-3xl animate-on-scroll">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">When it's working</span></div>
                <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] lg:text-5xl">
                    What changes once {{ strtolower($service['name']) }} is done right
                </h2>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                @foreach($service['outcomes'] as $i => $outcome)
                    <div class="rounded-[1.5rem] bg-white/[0.06] p-7 ring-1 ring-white/10 animate-on-scroll" data-delay="{{ min($i + 1, 4) }}">
                        <span class="font-display text-3xl font-bold text-red" style="color:#E31937">0{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-display text-xl font-bold text-white">{{ $outcome['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-white/75">{{ $outcome['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    @if(!empty($service['faq']))
    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div class="animate-on-scroll lg:sticky lg:top-24">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Questions</span></div>
                    <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        Asked before every {{ strtolower($service['name']) }} project
                    </h2>
                    <p class="mt-5 text-lg leading-8 text-gray-600">
                        And if yours isn't here, ask directly — you'll get a straight answer, not a brochure.
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-6 mt-8">Ask us a question</a>
                </div>

                <div x-data="{ open: 1 }" class="space-y-3">
                    @foreach($service['faq'] as $i => $item)
                        <div class="rounded-[1.25rem] bg-gray-50 ring-1 ring-gray-200 overflow-hidden animate-on-scroll" data-delay="{{ min($i + 1, 4) }}">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                                :aria-expanded="open === {{ $i + 1 }}"
                                aria-controls="faq-panel-{{ $i }}"
                                @click="open = (open === {{ $i + 1 }}) ? null : {{ $i + 1 }}"
                            >
                                <span class="font-display text-base font-bold text-navy">{{ $item['q'] }}</span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-gray-200 text-navy transition-transform duration-200" :class="open === {{ $i + 1 }} && 'rotate-45'">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z" clip-rule="evenodd" fill-rule="evenodd"/></svg>
                                </span>
                            </button>
                            <div id="faq-panel-{{ $i }}" x-show="open === {{ $i + 1 }}" x-cloak>
                                <p class="px-6 pb-6 text-sm leading-7 text-gray-600">{{ $item['a'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- RELATED SERVICES --}}
    <section class="bg-gray-50 py-20 lg:py-28">
        <div class="container-wx">
            <div class="mb-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl animate-on-scroll">
                    <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Explore more</span></div>
                    <h2 class="mt-5 font-display text-4xl font-bold leading-tight tracking-[-0.03em] text-navy lg:text-5xl">
                        Other services
                    </h2>
                </div>
                <a href="{{ route('services.index') }}" class="text-sm font-semibold text-red hover:text-red-700 transition-colors shrink-0 animate-on-scroll">
                    View all services →
                </a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($related as $i => $item)
                    <a href="{{ route('services.show', $item['slug']) }}" class="group animate-on-scroll rounded-[1.5rem] bg-white p-6 ring-1 ring-gray-200 transition-all duration-300 hover:-translate-y-1 hover:ring-navy/20" data-delay="{{ min($i + 1, 4) }}">
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="font-display text-lg font-bold leading-snug tracking-[-0.01em] text-navy">{{ $item['name'] }}</h3>
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 text-sm text-navy transition-colors duration-200 group-hover:border-red-600 group-hover:bg-red-600 group-hover:text-white" aria-hidden="true">→</span>
                        </div>
                        <p class="mt-4 text-sm leading-7 text-gray-600 line-clamp-3">{{ $item['summary'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-white py-20 lg:py-28">
        <div class="container-wx">
            <div class="relative overflow-hidden rounded-[2rem] bg-navy px-8 py-14 text-center lg:px-16 lg:py-20 animate-on-scroll" style="background-color:#1B2D5F">
                <div class="absolute inset-x-0 top-0 h-32 bg-[linear-gradient(180deg,rgba(255,255,255,0.06),rgba(255,255,255,0))]" aria-hidden="true"></div>
                <div class="relative">
                    <p class="label-sm text-white/60">Start a {{ strtolower($service['name']) }} project</p>
                    <h2 class="mx-auto mt-4 max-w-3xl font-display text-3xl font-bold leading-tight tracking-[-0.02em] text-white lg:text-5xl">
                        Let's plan yours — properly, from the first call.
                    </h2>
                    <p class="mx-auto mt-5 max-w-xl text-lg leading-8 text-white/70">
                        Tell us what needs to work. We'll tell you honestly what it takes, what it costs, and whether we're the right team for it.
                    </p>
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('contact') }}" class="btn btn-primary rounded-full px-7">Discuss this project</a>
                        <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}" class="text-sm font-semibold text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white transition-colors">
                            {{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

@php
    $homeContent = $page?->structured_content ?? [];
    $heroLabel = $homeContent['home_hero_label'] ?? 'Selected Work';
    $heroHeading = $homeContent['home_hero_heading'] ?? 'Digital experiences that move business forward.';
    $heroIntro = $homeContent['home_hero_intro'] ?? 'Webxpress Technologies designs and builds websites, digital products and business systems that help organizations operate, communicate and grow.';
    $projects = array_map(
        fn (array $project): array => [
            'number' => $project['number'], 'name' => $project['name'], 'category' => $project['category'],
            'year' => $project['year'], 'description' => $project['summary'], 'image' => $project['image'],
            'url' => route('work.show', $project['slug']),
        ],
        App\Services\ProjectCatalog::featured(4)
    );
@endphp

<section
    x-data="{ active: 0, projects: {{ json_encode($projects) }}, timer: null, interval: 5000, isTransitioning: false, imageLoaded: false, paused: false,
        get current() { return this.projects[this.active]; },
        get reducedMotion() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; },
        select(index) { if (this.isTransitioning || index === this.active) return; this.isTransitioning = true; this.imageLoaded = false; clearTimeout(this._transitionTimer); this._transitionTimer = setTimeout(() => { this.active = index; this.isTransitioning = false; this._transitionTimer = null; }, 300); this.resetTimer(); },
        startTimer() { this.stop(); if (this.paused || this.reducedMotion) return; this.timer = setInterval(() => this.select((this.active + 1) % this.projects.length), this.interval); },
        resetTimer() { this.startTimer(); },
        toggleTimer() { this.paused = !this.paused; this.paused ? this.stop() : this.startTimer(); },
        stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
        // Cleared alongside the interval so a queued slide transition cannot
        // write to a torn-down component after navigation.
        init() { this.startTimer(); },
        destroy() { this.stop(); clearTimeout(this._transitionTimer); this._transitionTimer = null; } }"
    @mouseenter="stop()"
    @mouseleave="startTimer()"
    @focusin="stop()"
    @focusout="startTimer()"
    class="relative min-h-[90svh] overflow-hidden bg-gray-900 md:min-h-[100svh]"
    role="region"
    aria-roledescription="carousel"
    aria-label="Featured projects showcase"
>
    <div class="absolute inset-0 z-0">
        <template x-for="(project, index) in projects" :key="index">
            <div :class="active === index ? 'opacity-100' : 'opacity-0'" :aria-hidden="active === index ? 'false' : 'true'" class="absolute inset-0 transition-opacity duration-700 ease-in-out">
                {{-- The wrapper is absolutely positioned and fills the viewport, so the image
                     is stretched by CSS rather than sized by its own aspect
                     ratio: intrinsic width/height would be advisory here and
                     would not reserve anything. The first slide is the LCP
                     candidate, so it is eager and prioritised; the rest are lazy
                     because they are display:none-adjacent until selected. --}}
                <img :src="project.image" alt="" :loading="index === 0 ? 'eager' : 'lazy'" class="h-full w-full object-cover object-center" :fetchpriority="index === 0 ? 'high' : 'auto'" decoding="async">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-900/90 via-gray-900/70 to-gray-900/35 md:from-gray-900/80 md:via-gray-900/45 md:to-transparent"></div>
            </div>
        </template>
    </div>
    <div class="container-wx relative z-10 flex min-h-[90svh] flex-col justify-center pt-24 pb-16 md:min-h-[100svh] md:pt-28">
        <div class="flex flex-col md:grid md:grid-cols-[1fr_auto] md:items-center md:gap-8">
            <div class="max-w-2xl">
                <div class="mb-6 flex items-center gap-3"><span class="accent-bar"></span><span class="label-sm text-white/60">{{ $heroLabel }}</span></div>
                <h1 class="heading-display mb-6 text-white">{{ $heroHeading }}</h1>
                <p class="body-lg mb-8 max-w-xl text-white/70">{{ $heroIntro }}</p>
                <div :class="isTransitioning ? 'opacity-0 translate-y-2' : 'opacity-100 translate-y-0'" class="mb-8 transition-all duration-300">
                    <div class="mb-3 flex flex-wrap items-center gap-4"><span class="label-sm text-white/60" x-text="current.number + ' / ' + String(projects.length).padStart(2,'0')"></span><span class="h-px w-8 bg-white/30"></span><span class="label-sm text-white/60" x-text="current.category"></span><span class="label-sm text-white/60" x-text="current.year"></span></div>
                    <h2 class="mb-3 text-2xl font-bold leading-tight text-white md:text-3xl" x-text="current.name"></h2>
                    <p class="max-w-lg text-base leading-relaxed text-white/70" x-text="current.description"></p>
                    <p class="sr-only" role="status" aria-live="polite" x-text="'Slide ' + (active + 1) + ' of ' + projects.length + ': ' + current.name"></p>
                </div>
                <div class="flex flex-wrap gap-3"><a href="{{ route('contact') }}" class="btn btn-primary">Start a Project</a><a :href="current.url" class="btn btn-ghost-white">View This Project</a><button type="button" @click="toggleTimer()" class="btn btn-ghost-white" :aria-label="paused ? 'Resume slideshow' : 'Pause slideshow'"><span aria-hidden="true" x-text="paused ? '▶ Play' : '❚❚ Pause'"></span></button></div>
            </div>
            <div class="mt-10 md:mt-0">
                <div class="hidden min-w-[200px] flex-col gap-2 md:flex">
                    <template x-for="(project, index) in projects" :key="index">
                        <button @click="select(index)" :aria-pressed="active === index" :aria-label="'Show project ' + (index + 1) + ': ' + project.name" :class="active === index ? 'bg-white/15 border-white/40' : 'bg-white/5 border-white/10 hover:bg-white/10 hover:border-white/25'" class="group min-h-[44px] cursor-pointer rounded-lg border px-4 py-3 text-left transition-all duration-200">
                            <div class="flex items-start gap-3"><span :class="active === index ? 'text-red-400' : 'text-white/60 group-hover:text-white/85'" class="mt-0.5 font-mono text-xs font-bold" x-text="project.number"></span><div><p class="mb-1 text-xs font-semibold leading-none text-white/60" x-text="project.category"></p><p :class="active === index ? 'text-white' : 'text-white/70 group-hover:text-white/85'" class="text-sm font-medium leading-snug" x-text="project.name"></p></div></div>
                        </button>
                    </template>
                </div>
                <div class="flex items-center gap-2 md:hidden"><template x-for="(project, index) in projects" :key="index"><button @click="select(index)" :aria-label="'View project: ' + project.name" :aria-pressed="active === index" :class="active === index ? 'bg-red-500 w-8' : 'bg-white/30 hover:bg-white/50 w-4'" class="h-1.5 min-w-[1rem] -my-3.5 cursor-pointer rounded-full transition-all duration-300"></button></template><span class="ml-3 font-mono text-xs text-white/70" x-text="(active + 1) + ' / ' + projects.length"></span></div>
            </div>
        </div>
    </div>
</section>

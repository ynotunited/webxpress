{{--
    SELECTED WORK — Asymmetric editorial portfolio grid
    Not identical card grids. Alternates sizes.
    Projects come from App\Services\ProjectCatalog, the same source as /work,
    so the homepage and the portfolio page never disagree.
--}}

@php
    $projects = App\Services\ProjectCatalog::featured(3);
@endphp

<section class="section-padding bg-white" aria-labelledby="selected-work-heading">
    <div class="container-wx">

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-10 lg:gap-20 mb-12">
            <div class="animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm">Selected Work</span>
                </div>
                <h2 id="selected-work-heading" class="heading-1">What we've built</h2>
            </div>
            <div class="flex items-end animate-on-scroll" data-delay="2">
                <a href="{{ route('work.index') }}" class="btn btn-ghost">View All Work</a>
            </div>
        </div>

        {{-- Asymmetric grid --}}
        <div class="grid grid-cols-1 gap-4 md:gap-5">

            {{-- Featured large project --}}
            @if(isset($projects[0]))
            <a
                href="{{ route('work.show', $projects[0]['slug']) }}"
                class="group block relative overflow-hidden rounded-lg bg-gray-100 animate-on-scroll"
                style="aspect-ratio: 16/7"
            >
                {{-- alt="" because the <h3> below already names the link;
                     a description here would be announced twice. --}}
                <img
                    src="{{ $projects[0]['image'] }}"
                    alt=""
                    class="w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-700 ease-out"
                    width="1600"
                    height="700"
                    loading="lazy"
                    decoding="async"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6 md:p-8">
                    <p class="label-sm text-white/70 mb-2">{{ $projects[0]['category'] }} · {{ $projects[0]['year'] }}</p>
                    <h3 class="text-xl md:text-2xl font-bold text-white">{{ $projects[0]['name'] }}</h3>
                </div>
                <div class="absolute top-4 right-4 flex items-center justify-center w-10 h-10 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>
            @endif

            {{-- Two smaller projects --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                @foreach(array_values(array_slice($projects, 1, 2)) as $i => $project)
                <a
                    href="{{ route('work.show', $project['slug']) }}"
                    class="group block relative overflow-hidden rounded-lg bg-gray-100 animate-on-scroll"
                    data-delay="{{ $i + 1 }}"
                    style="aspect-ratio: 4/3"
                >
                    {{-- alt="" because the <h3> below already names the link. --}}
                    <img
                        src="{{ $project['image'] }}"
                        alt=""
                        class="w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-700 ease-out"
                        width="1200"
                        height="900"
                        loading="lazy"
                        decoding="async"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-5 md:p-6">
                        <p class="label-sm text-white/70 mb-2">{{ $project['category'] }} · {{ $project['year'] }}</p>
                        <h3 class="text-lg md:text-xl font-bold text-white">{{ $project['name'] }}</h3>
                    </div>
                    <div class="absolute top-4 right-4 flex items-center justify-center w-10 h-10 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </div>
                </a>
                @endforeach
            </div>

        </div>

    </div>
</section>

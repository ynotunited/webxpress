{{--
    PROJECT CARD — Work index, detail pages and homepage sections

    Variants:
      standard  Image-led card with the title over a gradient (default)
      wide      Horizontal strip: image beside copy. Used as the last card in a
                row so a short final row reads as deliberate, not broken.
      compact   Framed card with copy below the image. Used where the card sits
                on a coloured surface (hero panel, related projects).

    Expects a ProjectCatalog entry: needs 'slug', 'name', 'category', 'year',
    'image', and optionally 'summary', 'number', 'stats'.
--}}

@props([
    'project',
    'variant' => 'standard',
    'number' => null,
    'delay' => null,
])

@php
    $href = route('work.show', $project['slug']);
    $meta = $project['category'].' · '.$project['year'];
    $animation = 'animate-on-scroll'.($delay !== null ? ' data-delay="'.min($delay, 4).'"' : '');
@endphp

@if($variant === 'wide')
    <a
        href="{{ $href }}"
        class="group block overflow-hidden rounded-[1.5rem] bg-gray-50 ring-1 ring-gray-200 transition-all duration-300 hover:ring-navy/20 hover:shadow-card-hover sm:col-span-2 {{ $animation }}"
    >
        <div class="grid h-full gap-0 sm:grid-cols-[0.9fr_1.1fr]">
            <div class="relative overflow-hidden bg-gray-100">
                <img
                    src="{{ $project['image'] }}"
                    alt=""
                    class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                    style="aspect-ratio:16/9"
                    loading="lazy"
                    decoding="async"
                    width="900"
                    height="506"
                >
            </div>

            <div class="flex flex-col justify-center p-6 lg:p-9">
                <p class="label-sm text-gray-500">
                    @if($number)<span class="font-mono text-navy">{{ $number }}</span> · @endif{{ $meta }}
                </p>
                <h3 class="mt-3 font-display text-2xl font-bold leading-tight tracking-[-0.02em] text-navy lg:text-3xl">
                    {{ $project['name'] }}
                </h3>
                @if(!empty($project['summary']))
                    <p class="mt-4 max-w-xl text-sm leading-7 text-gray-600">{{ $project['summary'] }}</p>
                @endif
                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-navy">
                    Read the case study
                    <span aria-hidden="true" class="transition-transform duration-200 group-hover:translate-x-1">→</span>
                </span>
            </div>
        </div>
    </a>
@elseif($variant === 'compact')
    <a
        href="{{ $href }}"
        class="group block overflow-hidden rounded-[1.5rem] bg-white ring-1 ring-gray-200 transition-all duration-300 hover:-translate-y-1 hover:ring-navy/20 {{ $animation }}"
    >
        <div class="relative overflow-hidden bg-gray-100">
            <img
                src="{{ $project['image'] }}"
                alt=""
                class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                loading="lazy"
                decoding="async"
                width="800"
                height="600"
            >
        </div>
        <div class="p-6">
            <p class="label-sm text-gray-500">
                @if($number)<span class="font-mono text-navy">{{ $number }}</span> · @endif{{ $meta }}
            </p>
            <h3 class="mt-3 font-display text-lg font-bold leading-snug tracking-[-0.01em] text-navy">
                {{ $project['name'] }}
            </h3>
            @if(!empty($project['summary']))
                <p class="mt-3 line-clamp-3 text-sm leading-7 text-gray-600">{{ $project['summary'] }}</p>
            @endif
            <span class="mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-600 transition-colors duration-200 group-hover:text-navy">
                View project
            </span>
        </div>
    </a>
@else
    <a
        href="{{ $href }}"
        class="group relative block overflow-hidden rounded-[1.5rem] bg-gray-100 {{ $animation }}"
    >
        <img
            src="{{ $project['image'] }}"
            alt=""
            class="h-full w-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-[1.03]"
            style="aspect-ratio:4/3"
            loading="lazy"
            decoding="async"
            width="800"
            height="600"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-navy/85 via-navy/25 to-transparent" aria-hidden="true"></div>

        <div class="absolute inset-x-0 top-0 flex items-start justify-between p-5">
            <p class="label-sm text-white/75">
                @if($number)<span class="font-mono">{{ $number }}</span> · @endif{{ $project['category'] }}
            </p>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm text-white ring-1 ring-white/20 backdrop-blur-sm transition-all duration-200 group-hover:border-transparent group-hover:bg-red group-hover:opacity-100 md:opacity-0" aria-hidden="true">→</span>
        </div>

        <div class="absolute inset-x-0 bottom-0 p-5 lg:p-6">
            <p class="label-sm text-white/60">{{ $project['year'] }}</p>
            <h3 class="mt-2 text-lg font-bold leading-snug text-white lg:text-xl">{{ $project['name'] }}</h3>
            @if(!empty($project['summary']))
                <p class="mt-2 hidden text-sm leading-6 text-white/70 lg:block">{{ $project['summary'] }}</p>
            @endif
        </div>
    </a>
@endif

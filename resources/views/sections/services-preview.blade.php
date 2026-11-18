{{--
    SERVICES PREVIEW — Editorial accordion-style, not 8 identical icon cards
    Hovering/clicking a service reveals a description and arrow CTA.

    Single source of truth for the service list: App\Services\ServiceCatalog.
    This view used to keep its own hardcoded copy of all eight services, which
    meant editing a service in three places (catalog, services index, here).

    Heading copy is editable from page_sections (`section_head` block keyed
    `services-preview`); it falls back to the shipped defaults.
--}}

@php
    use App\Services\ServiceCatalog;

    $services = collect(ServiceCatalog::all())
        ->map(fn (array $service, string $slug): array => $service + ['slug' => $slug])
        ->values();

    $label = $section?->get('label') ?: 'Services';
    $heading = $section?->get('heading') ?: 'What we build';
    $subheading = $section?->get('subheading');
@endphp

<section class="section-padding bg-gray-50" aria-labelledby="services-heading">
    <div class="container-wx">

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-10 lg:gap-20 mb-12">
            <div class="animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm">{{ $label }}</span>
                </div>
                <h2 id="services-heading" class="heading-1">{{ $heading }}</h2>
                @if ($subheading)
                    <p class="text-gray-500 mt-4 max-w-2xl leading-relaxed">{{ $subheading }}</p>
                @endif
            </div>
            <div class="flex items-end animate-on-scroll" data-delay="2">
                <a href="{{ route('services.index') }}" class="btn btn-ghost">View All Services</a>
            </div>
        </div>

        {{-- Services List --}}
        <div class="divide-y divide-gray-200">
            @foreach ($services as $index => $service)
                {{-- Hover and focus are equivalent here, so the summary is
                     reachable by keyboard, not just by pointer. --}}
                <div class="group/item animate-on-scroll" data-delay="{{ min($index + 1, 4) }}">
                    <a
                        href="{{ route('services.show', $service['slug']) }}"
                        class="group flex items-center gap-4 py-5 md:py-6 cursor-pointer transition-all duration-200"
                    >
                        {{-- Number --}}
                        <span class="text-xs font-mono font-bold text-gray-500 group-hover:text-red-500 transition-colors w-7 shrink-0">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </span>

                        {{-- Name --}}
                        <h3 class="text-xl md:text-2xl font-bold text-navy group-hover:text-red-600 transition-colors flex-1" style="color:#1B2D5F">
                            {{ $service['name'] }}
                        </h3>

                        {{-- Description (shown on hover/mobile) --}}
                        <p class="hidden md:block text-sm text-gray-500 max-w-xs leading-relaxed opacity-0 transition-opacity duration-300 group-hover/item:opacity-100 group-focus-within/item:opacity-100">
                            {{ $service['summary'] }}
                        </p>

                        {{-- Arrow --}}
                        <span class="flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 group-hover:border-red-500 group-hover:bg-red-500 transition-all duration-200 shrink-0">
                            <svg class="w-4 h-4 text-gray-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </span>
                    </a>

                    {{-- Mobile description --}}
                    <p class="md:hidden text-sm text-gray-500 pb-4 leading-relaxed">
                        {{ $service['summary'] }}
                    </p>
                </div>
            @endforeach
        </div>

    </div>
</section>

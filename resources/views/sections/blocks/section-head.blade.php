{{--
    SECTION HEADING — standalone, database driven.

    The other heading-shaped blocks (services-preview, testimonials,
    insights-preview) are overlays: they hand their copy to a section template
    that renders the list itself. This one is a heading on its own, so a page
    can be assembled from blocks without shipping a Blade template for each
    heading. The layout is in the design system, the copy is in the row.
--}}

@php
    $label = $section->get('label');
    $heading = $section->get('heading');
    $subheading = $section->get('subheading');
@endphp

@if (filled($heading) || filled($subheading))
    <section class="section-padding bg-white" aria-labelledby="{{ $section->key }}-heading">
        <div class="container-wx">
            <div class="max-w-3xl">
                @if (filled($label))
                    <div class="section-label">
                        <span class="accent-bar"></span>
                        <span class="label-sm">{{ $label }}</span>
                    </div>
                @endif

                @if (filled($heading))
                    <h2 id="{{ $section->key }}-heading" class="heading-1">{{ $heading }}</h2>
                @endif

                @if (filled($subheading))
                    <p class="body-lg text-gray-500 mt-4 leading-relaxed">{{ $subheading }}</p>
                @endif
            </div>
        </div>
    </section>
@endif

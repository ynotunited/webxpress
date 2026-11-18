{{--
    CARD GRID — database driven.
    Mirrors sections/industries-preview.blade.php, which remains the fallback.
--}}

@php
    $items = $section->getItems();
@endphp

@if ($items !== [])
    <section class="section-padding bg-gray-50" aria-labelledby="industries-heading">
        <div class="container-wx">
            <div class="mb-12 animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm">{{ $section->get('label', 'Industries') }}</span>
                </div>
                <h2 id="industries-heading" class="heading-1">{{ $section->get('heading') }}</h2>
                @if ($section->get('subheading'))
                    <p class="text-gray-500 mt-4 max-w-2xl leading-relaxed">{{ $section->get('subheading') }}</p>
                @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($items as $i => $item)
                    <div class="card hover:border-navy animate-on-scroll" data-delay="{{ min($i + 1, 4) }}" style="--hover-border:#1B2D5F">
                        <h3 class="text-base font-bold text-navy mb-2" style="color:#1B2D5F">{{ $item['name'] ?? '' }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $item['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{--
    IMAGE + TEXT — standalone, database driven.

    A two-column section. The image side is reversed by the `image_position`
    control, which the editor sets; the markup order does not change, so the
    mobile stacking order (copy first, image second) stays the same either way.
--}}

@php
    $label = $section->get('label');
    $heading = $section->get('heading');
    $body = $section->get('body');
    $imageUrl = $section->getImageUrl();
    $imageAlt = (string) $section->get('image_alt', '');
    $imageOnRight = $section->get('image_position') === 'right';
    $bullets = collect($section->getBullets())->pluck('text')->filter()->values();
    $cta = $section->getCta();
    $ctaUrl = $section->routeUrl($cta['route'] ?? null);
@endphp

@if (filled($heading) || filled($body) || $bullets->isNotEmpty() || $imageUrl !== null)
    <section class="section-padding bg-white" aria-labelledby="{{ $section->key }}-heading">
        <div class="container-wx">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">

                {{-- Copy. `order-2 lg:order-1` puts the image on the right. --}}
                <div class="order-2 lg:order-1">
                    @if (filled($label))
                        <div class="section-label">
                            <span class="accent-bar"></span>
                            <span class="label-sm">{{ $label }}</span>
                        </div>
                    @endif

                    @if (filled($heading))
                        <h2 id="{{ $section->key }}-heading" class="heading-1">{{ $heading }}</h2>
                    @endif

                    @if (filled($body))
                        <p class="body-lg mt-5 text-gray-500">{{ $body }}</p>
                    @endif

                    @if ($bullets->isNotEmpty())
                        <ul class="mt-7 space-y-3">
                            @foreach ($bullets as $bullet)
                                <li class="flex items-start gap-3 text-[15px] leading-7 text-gray-600">
                                    <svg class="mt-1 h-4 w-4 shrink-0" style="color:#E31937" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $bullet }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($ctaUrl && filled($cta['label'] ?? null))
                        <a href="{{ $ctaUrl }}" class="btn btn-primary mt-8">{{ $cta['label'] }}</a>
                    @endif
                </div>

                @if ($imageUrl !== null)
                    <div class="order-1 lg:order-2">
                        <div class="overflow-hidden rounded-[1.5rem] bg-gray-50">
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $imageAlt }}"
                                class="h-full w-full object-cover"
                                width="800"
                                height="600"
                                loading="lazy"
                            >
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>
@endif

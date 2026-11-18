{{--
    STATS BAND — standalone, database driven.

    Figures are a claim about the business, so the editor is not allowed to
    invent a source: only the value and its label are editable here, and the
    label is required to say what the number measures.
--}}

@php
    $figures = collect($section->getItems())
        ->filter(fn (array $item): bool => filled($item['value'] ?? null))
        ->values();

    $label = $section->get('label');
    $heading = $section->get('heading');
    $subheading = $section->get('subheading');
@endphp

@if ($figures->isNotEmpty())
    <section class="section-padding bg-gray-50" aria-labelledby="{{ $section->key }}-heading">
        <div class="container-wx">

            @if (filled($label) || filled($heading) || filled($subheading))
                <div class="mb-12 max-w-3xl">
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
                        <p class="text-gray-500 mt-4 leading-relaxed">{{ $subheading }}</p>
                    @endif
                </div>
            @endif

            <dl class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
                @foreach ($figures as $i => $figure)
                    <div class="animate-on-scroll" data-delay="{{ min($i + 1, 4) }}">
                        <dt class="sr-only">{{ $figure['label'] ?? '' }}</dt>
                        <dd>
                            <span class="block font-display text-5xl font-bold leading-none text-navy" style="color:#1B2D5F">
                                {{ $figure['value'] }}
                            </span>
                            <span class="mt-3 block text-sm leading-6 text-gray-500">
                                {{ $figure['label'] ?? '' }}
                            </span>
                        </dd>
                    </div>
                @endforeach
            </dl>

        </div>
    </section>
@endif

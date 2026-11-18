{{--
    BLOCK: bio — "Who We Are" editorial company intro

    Design shell lives here in Blade; every string comes from the
    page_sections row. All values are escaped.
--}}

@php
    $bioLabel = $section->get('label', 'Who We Are');
    $bioQuote = $section->get('quote');
    $bioParagraphs = $section->getParagraphs();
    $bioCta = (array) $section->get('cta', []);
    $bioCtaUrl = filled($bioCta['route'] ?? null) && Route::has($bioCta['route'])
        ? route($bioCta['route'])
        : null;
@endphp

<section class="section-padding bg-white" aria-labelledby="who-we-are-heading">
    <div class="container-wx">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

            {{-- Left: Editorial text --}}
            <div class="animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm" id="who-we-are-heading">{{ $bioLabel }}</span>
                </div>

                @if (filled($bioQuote))
                    <blockquote class="text-2xl md:text-3xl font-bold text-navy leading-tight mb-8" style="color:#1B2D5F">
                        &ldquo;{{ $bioQuote }}&rdquo;
                    </blockquote>
                @endif

                @foreach ($bioParagraphs as $index => $bioParagraph)
                    @if ($index === 0)
                        <p class="body-lg mb-5">{{ $bioParagraph }}</p>
                    @else
                        <p class="body-base text-gray-500 mb-8">{{ $bioParagraph }}</p>
                    @endif
                @endforeach

                @if ($bioCtaUrl && filled($bioCta['label'] ?? null))
                    <a href="{{ $bioCtaUrl }}" class="btn btn-secondary">{{ $bioCta['label'] }}</a>
                @endif
            </div>

            {{-- Right: Stats grid --}}
            <div class="animate-on-scroll" data-delay="2">
                <div class="grid grid-cols-2 gap-6">
                    @foreach ($section->getStats() as $bioStat)
                        @php $bioVariant = in_array($bioStat['variant'] ?? 'plain', ['plain', 'inverted', 'dashed'], true) ? $bioStat['variant'] : 'plain'; @endphp

                        @if ($bioVariant === 'inverted')
                            <div class="p-6" style="background-color:#1B2D5F; border-radius:8px">
                                <p class="text-4xl md:text-5xl font-bold text-white mb-1">{{ $bioStat['value'] }}</p>
                                @if (filled($bioStat['label'] ?? null))
                                    <p class="text-sm text-white/70 font-medium">{{ $bioStat['label'] }}</p>
                                @endif
                            </div>
                        @elseif ($bioVariant === 'dashed')
                            <div class="p-6 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center">
                                <div class="text-center">
                                    @if (filled($bioStat['prefix'] ?? null))
                                        <p class="text-2xl font-bold text-gray-700 mb-1">{{ $bioStat['prefix'] }}</p>
                                    @endif
                                    <p class="text-4xl font-bold" style="color:#E31937">{{ $bioStat['value'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="p-6 bg-gray-50 rounded-lg border border-gray-100">
                                <p class="text-4xl md:text-5xl font-bold text-navy mb-1" style="color:#1B2D5F">{{ $bioStat['value'] }}</p>
                                @if (filled($bioStat['label'] ?? null))
                                    <p class="text-sm text-gray-500 font-medium">{{ $bioStat['label'] }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>

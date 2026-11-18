{{--
    LOGO STRIP — client marks, database driven.
    Mirrors sections/trusted-by.blade.php, which remains the fallback.
--}}

@php
    $items = $section->getItems();
    // Shared with sections/trusted-by.blade.php. Resolves one entry per logo
    // and prefers the WebP version, so a converted logo is not listed twice
    // alongside the PNG it replaced.
    $logos = client_logos();
@endphp

@if ($items !== [])
    <section class="py-12 md:py-16 border-b border-gray-100" aria-label="{{ $section->get('label', 'Trusted by') }}">
        <div class="container-wx">

            <div class="flex flex-col sm:flex-row sm:items-center gap-8 md:gap-12">
                <p class="label-sm text-gray-600 shrink-0">{{ $section->get('label', 'Trusted by') }}</p>
                {{-- role="group" so the focusable marquee is announced as a labelled region;
         tabindex="0" is the keyboard way to pause the auto-scroll. --}}
    <div class="wx-client-marquee" role="group" tabindex="0" aria-label="Client logos. Hover or focus to pause scrolling.">
                    <div class="wx-client-track">
                        {{-- Duplicated for a seamless loop; the second pass is
                             aria-hidden so each logo is announced once. --}}
                        @for ($copy = 0; $copy < 2; $copy++)
                            @if ($logos !== [])
                                @foreach ($logos as $logo)
                                    {{-- alt="" on the duplicate copy: aria-hidden on the parent is the primary
         guard, but an empty alt is what actually prevents a second
         announcement from screen readers that skip aria-hidden. --}}
                    <div class="wx-client-logo" title="{{ $logo['name'] }}" @if($copy === 1) aria-hidden="true" @endif><img src="{{ asset($logo['path']) }}" alt="{{ $copy === 0 ? $logo['name'] : '' }}" loading="lazy" width="136" height="52" decoding="async"></div>
                                @endforeach
                            @else
                                @foreach ($items as $client)
                                    <div class="wx-client-logo wx-client-logo-text" title="{{ $client['name'] ?? '' }}" @if($copy === 1) aria-hidden="true" @endif>{{ $client['abbr'] ?? '' }}</div>
                                @endforeach
                            @endif
                        @endfor
                    </div>
                </div>
            </div>

        </div>
    </section>
@endif

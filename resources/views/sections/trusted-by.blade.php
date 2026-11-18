{{--
    TRUSTED BY — Client Logo Strip
    Visually restrained. Static grid on mobile, flex on desktop.
--}}

@php
    // Shared with sections/blocks/logo-strip. Resolves one entry per logo and
    // prefers the WebP version, so a converted logo is not listed twice
    // alongside the PNG it replaced.
    $logos = client_logos();

    $clients = [
        ['name' => 'University of Lagos', 'abbr' => 'UNILAG'],
        ['name' => 'Access Bank', 'abbr' => 'Access'],
        ['name' => 'Guarantee Trust Bank', 'abbr' => 'GTBank'],
        ['name' => 'National University Commission', 'abbr' => 'NUC'],
        ['name' => 'Hilton Hotels', 'abbr' => 'Hilton'],
        ['name' => 'SunTrust Bank', 'abbr' => 'SunTrust'],
    ];
@endphp

<section class="py-12 md:py-16 border-b border-gray-100" aria-label="Trusted by">
    <div class="container-wx">

        <div class="flex flex-col sm:flex-row sm:items-center gap-8 md:gap-12">
            <p class="label-sm text-gray-600 shrink-0">Trusted by</p>
            {{-- role="group" so the focusable marquee is announced as a labelled region.
         tabindex="0" is required, not decorative: it is the keyboard equivalent
         of hovering, which is how the auto-scroll pauses. --}}
    <div class="wx-client-marquee" role="group" tabindex="0" aria-label="Client logos. Hover or focus to pause scrolling.">
                <div class="wx-client-track">
{{-- Duplicated for a seamless loop; the second pass is
         aria-hidden so each logo is announced once. --}}
        @for ($copy = 0; $copy < 2; $copy++)
            @if ($logos !== [])
                @foreach($logos as $logo)
                    {{-- alt="" on the duplicate: it is inside an aria-hidden
                         parent, but an empty alt is what actually stops a screen
                         reader that ignores aria-hidden from reading it twice. --}}
                    <div class="wx-client-logo" title="{{ $logo['name'] }}" @if($copy === 1) aria-hidden="true" @endif><img src="{{ asset($logo['path']) }}" alt="{{ $copy === 0 ? $logo['name'] : '' }}" loading="lazy" width="136" height="52" decoding="async"></div>
                @endforeach
            @else
                @foreach($clients as $client)
                    <div class="wx-client-logo wx-client-logo-text" title="{{ $client['name'] }}" @if($copy === 1) aria-hidden="true" @endif>{{ $client['abbr'] }}</div>
                @endforeach
            @endif
        @endfor
                </div>
            </div>
        </div>

    </div>
</section>

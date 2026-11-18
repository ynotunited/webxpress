<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    @isset($seoTitle)
        <title>{{ $seoTitle }}</title>
    @else
        <title>{{ $title ?? config('app.name', 'Webxpress Technologies') }}</title>
    @endisset

    <meta name="description" content="{{ $seoDescription ?? $title ?? config('app.name', 'Webxpress Technologies') }}">

    @isset($seoCanonical)
        <link rel="canonical" href="{{ $seoCanonical }}">
    @endisset

    {{-- Open Graph. Link unfurlers do not read the visible page, so every
         preview needs its own title and image. A view can still override
         either one; otherwise the site-wide settings and shipped image win. --}}
    @php
        $ogTitle ??= site_setting('og_title') ?: ($seoTitle ?? $title ?? site_name());
        $ogDescription ??= $seoDescription ?? null;
        $ogImage ??= og_image_url();
    @endphp
    <meta property="og:site_name" content="{{ site_name() }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    @if (filled($ogDescription))
        <meta property="og:description" content="{{ $ogDescription }}">
    @endif
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ogTitle }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Structured data. Crawlers that don't execute JavaScript read this
         instead of the rendered copy; see components/structured-data. --}}
    @include('components.structured-data')

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ brand_favicon() }}">

    {{-- Fonts are served from /fonts and declared in resources/css/fonts.css.
         Both Google Fonts origins used to sit here, which made every page view
         hand Google the visitor's IP, user-agent and exact URL — and forced any
         content security policy to permit those two origins to render at all.
         Self-hosting means the head carries no third-party origin whatsoever.

         The preload covers the body-text weight, which is the one every page
         asks for first; `crossorigin` is required for font preloads even
         same-origin, because fonts are fetched in anonymous mode. --}}
    <link
        rel="preload"
        as="font"
        type="font/woff2"
        href="{{ asset('fonts/plus-jakarta-sans-400-latin.woff2') }}"
        crossorigin
    >

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ csp_nonce() }}">window.APP_VERSION = @json(asset_version());</script>

    {{-- Page-specific entries. Each is a separate Vite chunk that only the
         page that declares it downloads, instead of inlining the code into
         the HTML where it could never be cached separately. --}}
    @stack('scripts')
</head>
<body class="bg-white antialiased">
<a
    href="#main-content"
    class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:rounded-lg focus:bg-navy focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-white"
>Skip to content</a>

<div id="offline-status" class="wx-offline-status" role="status" aria-live="polite" hidden>
    You are offline. Showing the latest saved version.
</div>

    {{-- Header --}}
    @include('components.header')

    {{-- Main Content --}}
    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @include('components.footer')

    <noscript>
        {{-- Nonced like every other inline block: style-src carries a nonce, so
             an unnonced <style> here would be dropped and the scroll-reveal
             animations would stay stuck at opacity 0 for anyone without JS —
             a failure that would be silent, because it only affects visitors
             who cannot tell you about it. --}}
        <style nonce="{{ csp_nonce() }}">.animate-on-scroll { opacity: 1 !important; transform: none !important; }</style>
    </noscript>
</body>
</html>

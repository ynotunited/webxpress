{{-- <footer> is already the contentinfo landmark; the explicit role is redundant. --}}
<footer class="wx-cinematic-footer">
    <div class="wx-footer-marquee" aria-hidden="true">
        <div>Clarity that moves business forward <span>✦</span> Digital systems that work harder <span>✦</span> Built for ambitious teams <span>✦</span> Clarity that moves business forward <span>✦</span> Digital systems that work harder <span>✦</span></div>
    </div>

    <div class="wx-footer-grid" aria-hidden="true"></div>
    <div class="wx-footer-wordmark" aria-hidden="true">WEBXPRESS</div>

    <div class="wx-footer-center">
        <h2>Ready to begin?</h2>
        <a href="{{ route('contact') }}" class="wx-footer-pill wx-footer-pill-primary"><span aria-hidden="true">↗</span> Start a project</a>
        <a href="{{ route('work.index') }}" class="wx-footer-pill wx-footer-pill-primary"><span aria-hidden="true">◌</span> Explore our work</a>

        {{-- aria-current mirrors the header so a screen reader user knows which page
             this is when they navigate via the footer. --}}
        <nav class="wx-footer-secondary" aria-label="Footer">
            <a href="{{ route('work.index') }}" class="wx-footer-pill" @if (request()->routeIs('work.*')) aria-current="page" @endif>Work</a>
            <a href="{{ route('services.index') }}" class="wx-footer-pill" @if (request()->routeIs('services.*')) aria-current="page" @endif>Services</a>
            <a href="{{ route('about') }}" class="wx-footer-pill" @if (request()->routeIs('about')) aria-current="page" @endif>About</a>
            <a href="{{ route('insights.index') }}" class="wx-footer-pill" @if (request()->routeIs('insights.*')) aria-current="page" @endif>Insights</a>
            @foreach (\App\Support\SiteNavigation::legalItems() as $item)
                <a href="{{ $item['href'] }}" class="wx-footer-pill" {{ $item['active'] ? 'aria-current="page"' : '' }}>{{ $item['label'] }}</a>
            @endforeach
            <a href="mailto:hello@webxpresstech.com.ng" class="wx-footer-pill" @if (request()->routeIs('contact')) aria-current="page" @endif>Contact</a>
        </nav>
    </div>

    <div class="wx-footer-bottom">
        <div class="wx-footer-brand"><span>&copy; {{ date('Y') }} Webxpress Technologies. All rights reserved.</span></div>
        <div class="wx-footer-credit">Crafted with <span>♥</span> by Webxpress MadeIT</div>
        {{-- Honour prefers-reduced-motion: an unconditional smooth scroll is exactly the
             vestibular trigger that setting exists to suppress. --}}
            <button type="button" onclick="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })" aria-label="Back to top" class="wx-footer-top">↑</button>
    </div>
</footer>

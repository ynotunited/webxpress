@php
    $homeContent = $page?->structured_content ?? [];
    $heroLabel = $homeContent['home_hero_label'] ?? 'Web design, software and digital systems';
    $heroHeading = $homeContent['home_hero_heading'] ?? 'We build the digital backbone for ambitious teams.';
    $heroIntro = $homeContent['home_hero_intro'] ?? 'Webxpress Technologies designs and engineers websites, SaaS platforms, client portals and business systems that help organizations sell, operate and scale with confidence.';
@endphp

<section class="wx-template-hero">
    <div class="wx-template-glow" aria-hidden="true"></div>
    <div class="container-wx wx-template-inner">
        <div class="wx-template-announcement">
            <span>Webxpress Technologies</span>
            <span class="wx-template-dot">•</span>
            <span>Digital systems for ambitious teams</span>
            <a href="{{ route('about') }}">About us <span aria-hidden="true">↗</span></a>
        </div>

        <p class="wx-template-eyebrow"><span></span>{{ $heroLabel }}</p>
        <h1>{{ $heroHeading }}</h1>
        <p class="wx-template-intro">{{ $heroIntro }}</p>

        <div class="wx-template-actions">
            <a href="{{ route('contact') }}" class="wx-template-button wx-template-button-primary">Start a project <span>↗</span></a>
            <a href="{{ route('work.index') }}" class="wx-template-button">Explore our work <span>↗</span></a>
        </div>

        <div class="wx-template-visual" role="img" aria-label="Selected Webxpress work">
            <div class="wx-template-project">
                <img src="{{ asset('images/dashboard.png') }}" alt="Webxpress digital systems dashboard" width="1875" height="925" fetchpriority="high" decoding="async">
            </div>
        </div>
    </div>
</section>

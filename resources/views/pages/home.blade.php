{{--
    HOMEPAGE — Webxpress Technologies
    Premium cinematic corporate website homepage.
    Mobile-first layout. No vibe-coded patterns.

    Every section below resolves through the block dispatcher, which hands the
    section its editable copy from page_sections and falls back to the shipped
    markup when a block is absent. Hero and Selected Work are still shipped
    Blade only (not yet promoted).
--}}

@php
    $block = fn (string $key) => $blocks->get($key);
    // Set to 'template' to use the alternate centered hero, or 'showcase'
    // to keep the current featured-project hero.
    $heroVariant = $page?->structured_content['home_hero_variant'] ?? 'template';
@endphp

<x-layouts.app
    title="Webxpress Technologies — Web & SaaS Built for Africa"
    seoTitle="Webxpress Technologies | Web Development, SaaS & Digital Solutions in Nigeria"
    seoDescription="Webxpress Technologies delivers fast, scalable web and SaaS solutions for Nigerian businesses. Web development, mobile apps, branding, AI solutions and more since 2010."
    ogTitle="Webxpress Technologies — Web & SaaS Built for Africa"
    ogDescription="Fast, scalable digital solutions for businesses that can't wait for slow tech."
>

    {{-- =============================================
         SECTION 1: HERO / INTERACTIVE PROJECT SHOWCASE
         ============================================= --}}
    @if ($heroVariant === 'template')
        @include('sections.hero-template')
    @else
        @include('sections.hero')
    @endif

    {{-- =============================================
         SECTION 2: TRUSTED BY
         ============================================= --}}
    @include('sections.blocks', ['section' => $block('trusted-by'), 'key' => 'trusted-by'])

    {{-- =============================================
         SECTION 3: WHO WE ARE
         ============================================= --}}
    @include('sections.blocks', ['section' => $block('who-we-are'), 'key' => 'who-we-are'])

    {{-- =============================================
         SECTION 4: SERVICES
         ============================================= --}}
    @include('sections.services-preview', ['section' => $block('services-preview')])

    {{-- =============================================
         SECTION 5: SELECTED WORK
         ============================================= --}}
    @include('sections.selected-work')

    {{-- =============================================
         SECTION 6: HOW WE WORK
         ============================================= --}}
    @include('sections.blocks', ['section' => $block('how-we-work'), 'key' => 'how-we-work'])

    {{-- =============================================
         SECTION 7: INDUSTRIES
         ============================================= --}}
    @include('sections.blocks', ['section' => $block('industries-preview'), 'key' => 'industries-preview'])

    {{-- =============================================
         SECTION 8: TESTIMONIALS
         ============================================= --}}
    @include('sections.testimonials', ['section' => $block('testimonials')])

    {{-- =============================================
         SECTION 9: MAJOR CTA
         ============================================= --}}
    @include('sections.blocks', ['section' => $block('major-cta'), 'key' => 'major-cta'])

    {{-- =============================================
         SECTION 10: INSIGHTS
         ============================================= --}}
    @include('sections.insights-preview', ['section' => $block('insights-preview')])

    @push('scripts')
        @vite('resources/js/testimonials.js')
    @endpush

</x-layouts.app>

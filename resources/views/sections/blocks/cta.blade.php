{{--
    CALL TO ACTION — database driven.
    Mirrors sections/major-cta.blade.php, which remains the fallback.

    Buttons are typed, not raw hrefs: a button stores a route *name*, so a
    renamed route can never leave a dead link in the database. A button whose
    route no longer exists is dropped rather than rendered broken.
--}}

@php
    $heading = $section->get('heading');
    $accent = $section->get('heading_accent');
    $label = $section->get('label', 'Start a Project');
    $body = $section->get('body');

    // Refresh the shipped homepage CTA without overriding future admin edits.
    if ($section->key === 'major-cta' && $heading === 'Have a business problem') {
        $label = 'Your next move';
        $heading = 'Stop working around';
        $accent = 'the problem.';
        $body = 'Bring us the messy workflow, the slow website or the idea that is ready to become real. We will help you find the clearest next step and build what earns its place in the business.';
    }
    $buttons = collect($section->getButtons())
        ->map(function (array $button) use ($section): ?array {
            $route = $button['route'] ?? null;

            if (blank($route) || ! Route::has($route)) {
                return null;
            }

            return [
                'label' => ($section->key === 'major-cta' && ($button['label'] ?? '') === 'Start a Project')
                    ? 'Tell us what is stuck'
                    : (($section->key === 'major-cta' && ($button['label'] ?? '') === 'View Our Work')
                        ? 'See what we have built'
                        : ($button['label'] ?? '')),
                'url' => route($route),
                'style' => ($button['style'] ?? 'primary') === 'ghost' ? 'btn btn-ghost' : 'btn btn-primary',
            ];
        })
        ->filter()
        ->values();
@endphp

@if (filled($heading))
    <section class="section-padding wx-major-cta" aria-labelledby="cta-heading">
        <div class="container-wx">
            <div class="max-w-3xl animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm">{{ $label }}</span>
                </div>
                <h2 id="cta-heading" class="heading-display mb-6">
                    {{ $heading }}
                    @if (filled($accent))
                        <br>
                        <span style="color:#E31937">{{ $accent }}</span>
                    @endif
                </h2>
                @if ($body)
                    <p class="body-lg text-gray-500 mb-10 max-w-xl">{{ $body }}</p>
                @endif
                <div class="flex flex-wrap gap-4">
                    @foreach ($buttons as $button)
                        <a href="{{ $button['url'] }}" class="{{ $button['style'] }} text-lg px-8 py-4">{{ $button['label'] }}</a>
                    @endforeach
                </div>
                @if ($section->key === 'major-cta' && $heading === 'Stop working around')
                    <p class="wx-major-cta-note">No hard sell. Just a useful first conversation.</p>
                @endif
            </div>
        </div>
    </section>
@endif

{{--
    NUMBERED PROCESS STEPS — database driven.
    Mirrors sections/how-we-work.blade.php, which remains the fallback.
--}}

@php
    $items = $section->getItems();
@endphp

@if ($items !== [])
    <section class="section-padding" style="background-color:#1B2D5F" aria-labelledby="process-heading">
        <div class="container-wx">

            <div class="mb-12 animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm text-white/70">{{ $section->get('label', 'How We Work') }}</span>
                </div>
                <h2 id="process-heading" class="heading-1 text-white">{{ $section->get('heading') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach ($items as $i => $step)
                    <div class="animate-on-scroll" data-delay="{{ $i + 1 }}">
                        {{-- Step number --}}
                        <div class="flex items-center gap-3 mb-5">
                            <span class="text-5xl font-black text-white/10 leading-none select-none" aria-hidden="true">{{ $step['number'] ?? '' }}</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                        </div>

                        <h3 class="text-xl font-bold text-white mb-3">{{ $step['title'] ?? '' }}</h3>
                        <p class="text-sm text-white/60 leading-relaxed">{{ $step['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>

        </div>
    </section>
@endif

{{--
    FAQ — standalone, database driven.

    Answers are rich text, so they are read through the model's sanitising
    accessor. Accordion state is native <details>/<summary>, which means it
    works with JavaScript disabled, is keyboard operable, and is announced
    correctly by screen readers — no ARIA has to be kept in sync with state.
--}}

@php
    $questions = collect($section->getItems())
        ->filter(fn (array $item): bool => filled($item['question'] ?? null))
        ->map(fn (array $item): array => [
            'question' => $item['question'],
            'answer' => $section->sanitizeHtml((string) ($item['answer'] ?? '')),
        ])
        ->filter(fn (array $item): bool => $item['answer'] !== '')
        ->values();

    $label = $section->get('label');
    $heading = $section->get('heading');
@endphp

@if ($questions->isNotEmpty())
    <section class="section-padding bg-white" aria-labelledby="{{ $section->key }}-heading">
        <div class="container-wx">
            <div class="grid gap-10 lg:grid-cols-[0.75fr_1.25fr] lg:gap-20">

                <div>
                    @if (filled($label))
                        <div class="section-label">
                            <span class="accent-bar"></span>
                            <span class="label-sm">{{ $label }}</span>
                        </div>
                    @endif

                    @if (filled($heading))
                        <h2 id="{{ $section->key }}-heading" class="heading-1">{{ $heading }}</h2>
                    @endif
                </div>

                {{-- `open` on the first row shows the section is an accordion
                     without making every answer visible at once. --}}
                <div class="divide-y divide-gray-200 border-y border-gray-200">
                    @foreach ($questions as $i => $item)
                        <details class="group py-5" @if ($i === 0) open @endif>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-6 font-display text-lg font-bold text-navy marker:content-none" style="color:#1B2D5F">
                                <span>{{ $item['question'] }}</span>
                                <svg class="mt-1 h-5 w-5 shrink-0 transition-transform duration-200 group-open:rotate-45" style="color:#E31937" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path d="M10 4v12M4 10h12" stroke-linecap="round" />
                                </svg>
                            </summary>
                            <div class="prose-wx mt-4 text-[15px]">{!! $item['answer'] !!}</div>
                        </details>
                    @endforeach
                </div>

            </div>
        </div>
    </section>
@endif

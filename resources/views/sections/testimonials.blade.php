{{--
    TESTIMONIALS — Rendered only when published reviews exist.

    Reviews come from the client_reviews table; only the heading copy is
    editable from page_sections (`section_head` block keyed `testimonials`).

    Paged in fixed sets rather than scrolled on a loop. Every card is in the
    HTML and the pager only decides which are on the current set, so the quotes
    survive without JavaScript — where they simply stack — and each voice is
    credited to the project it describes rather than to a company typed by hand.
--}}
@php
    $label = $section?->get('label') ?: 'Client Voices';
    $heading = $section?->get('heading') ?: 'What our clients say';
    $subheading = $section?->get('subheading') ?: "Real outcomes from the teams we've built with — and the work we're proud to stand behind.";

    // Reviews per set. Declared once and handed to both the pager and the dot
    // list, so the controls can never disagree with the grid about how many
    // voices a "set" holds.
    $perPage = 4;
    $sets = max(1, (int) ceil($reviews->count() / $perPage));
@endphp
@if($reviews->isNotEmpty())
<section class="section-padding bg-white" aria-labelledby="testimonials-heading">
    <div class="container-wx">
        <div class="mb-12 animate-on-scroll">
            <div class="section-label"><span class="accent-bar"></span><span class="label-sm">{{ $label }}</span></div>
            <h2 id="testimonials-heading" class="heading-1">{{ $heading }}</h2>
            <p class="text-gray-500 mt-4 max-w-2xl leading-relaxed">
                {{ $subheading }}
            </p>
        </div>

        <div data-client-voices class="animate-on-scroll">
            <ul class="wx-voices-grid" role="list">
                @foreach($reviews as $i => $review)
                    <li class="wx-testimonial-card" data-voice-card {{ $i >= $perPage ? 'hidden' : '' }}>
                        <figure class="flex h-full flex-col justify-between gap-5">
                            <blockquote>
                                <span aria-hidden="true">&ldquo;</span>
                                <p>{{ $review->quote }}</p>
                            </blockquote>
                            <figcaption>
                                <strong>{{ $review->client_name }}</strong>
                                <small>{{ $review->client_role }}</small>

                                {{-- Credited from the work catalogue when the review
                                     points at a project, so the company named here is
                                     one whose work is published. Falls back to the
                                     stored name when a review has no project. --}}
                                @php($credited = $review->creditedCompany())
                                @if($credited !== '')
                                    <p class="wx-voice-client">
                                        <span class="sr-only">Client:</span>
                                        @if($review->project_slug && $review->workProject())
                                            <a href="{{ route('work.show', $review->project_slug) }}">{{ $credited }}</a>
                                        @else
                                            <span>{{ $credited }}</span>
                                        @endif
                                    </p>
                                @endif
                            </figcaption>
                        </figure>
                    </li>
                @endforeach
            </ul>

            {{-- Announced after each move so the change is not silent. The
                 buttons keep focus, which is what a keyboard user expects after
                 pressing them. --}}
            <p class="sr-only" role="status" aria-live="polite">
                <span data-voices-status>Showing client voices 1 to {{ min($reviews->count(), $perPage) }} of {{ $reviews->count() }}.</span>
            </p>

            @if($sets > 1)
            <div class="wx-voices-controls" data-voices-controls>
                <button
                    type="button"
                    class="wx-voices-arrow"
                    data-voices-prev
                    disabled
                >
                    <span aria-hidden="true">&larr;</span>
                    <span class="wx-voices-arrow-label">Back</span>
                </button>

                @if($sets > 1)
                <ol class="wx-voices-dots" role="list">
                    @for($n = 0; $n < $sets; $n++)
                        <li>
                            <button
                                type="button"
                                class="wx-voices-dot"
                                data-voices-page="{{ $n }}"
                                {{ $n === 0 ? 'aria-current=true' : '' }}
                                aria-label="Go to set {{ $n + 1 }}"
                            >
                                <span class="sr-only">Set {{ $n + 1 }}</span>
                            </button>
                        </li>
                    @endfor
                </ol>
                @endif

                <button
                    type="button"
                    class="wx-voices-arrow"
                    data-voices-next
                >
                    <span class="wx-voices-arrow-label">Next</span>
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

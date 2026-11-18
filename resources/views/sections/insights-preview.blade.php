{{--
    INSIGHTS PREVIEW — latest three published posts.

    Used to render a hardcoded "Insights are coming soon." line regardless of
    whether articles existed, which was plainly false. Posts come from the
    posts table; heading copy is editable from page_sections (`section_head`
    block keyed `insights-preview`).
--}}

@php
    $label = $section?->get('label') ?: 'Insights';
    $heading = $section?->get('heading') ?: 'Thinking out loud';
    $subheading = $section?->get('subheading');
@endphp

<section class="section-padding bg-gray-50" aria-labelledby="insights-heading">
    <div class="container-wx">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-10 lg:gap-20 mb-12">
            <div class="animate-on-scroll">
                <div class="section-label"><span class="accent-bar"></span><span class="label-sm">{{ $label }}</span></div>
                <h2 id="insights-heading" class="heading-1">{{ $heading }}</h2>
                @if ($subheading)
                    <p class="text-gray-500 mt-4 max-w-2xl leading-relaxed">{{ $subheading }}</p>
                @endif
            </div>
            <div class="flex items-end animate-on-scroll" data-delay="2">
                <a href="{{ route('insights.index') }}" class="btn btn-ghost">All Insights</a>
            </div>
        </div>

        @if ($posts->isEmpty())
            <p class="text-gray-600 italic animate-on-scroll">New articles coming soon.</p>
        @else
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $i => $post)
                    <article class="animate-on-scroll" data-delay="{{ min($i + 1, 4) }}">
                        @if ($post->cover_url)
                            <img
                                src="{{ $post->cover_url }}"
                                alt=""
                                class="w-full aspect-[16/9] object-cover rounded-xl mb-5"
                                width="800"
                                height="450"
                                loading="lazy"
                                decoding="async"
                            >
                        @endif

                        @if ($post->category)
                            <p class="label-sm mb-2">{{ $post->category->name }}</p>
                        @endif

                        <h3 class="text-xl font-bold leading-snug mb-2" style="color:#1B2D5F">
                            <a href="{{ route('insights.show', $post->slug) }}" class="hover:text-red-600 transition-colors">
                                {{ $post->title }}
                            </a>
                        </h3>

                        <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ $post->excerpt }}</p>

                        <time class="text-xs text-gray-600" datetime="{{ $post->published_at?->toDateString() }}">
                            {{ $post->published_at?->format('j M Y') }}
                        </time>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>

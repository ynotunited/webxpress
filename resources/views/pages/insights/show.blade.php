{{--
    STOPGAP template. The designed Insights article layout is Phase 3 work
    (see the section-block rollout). This renders real, semantic, indexable
    content using existing utility classes so a published post is never
    served as an empty stub.
--}}
<x-layouts.app
    :title="$post->seo_title ?: $post->title"
    :seoTitle="$post->seo_title ?: $post->title.' — '.site_name()"
    :seoDescription="$post->seo_description ?: $post->excerpt"
>
    <article class="pt-28 pb-20">
        <div class="container-wx max-w-3xl">

            <header class="mb-10">
                @if ($post->category)
                    <p class="section-label mb-5">
                        <span class="accent-bar"></span>
                        <span class="label-sm">{{ $post->category->name }}</span>
                    </p>
                @endif

                <h1 class="heading-1">{{ $post->title }}</h1>

                <p class="mt-4 text-sm text-gray-500">
                    <time datetime="{{ $post->published_at?->toDateString() }}">
                        {{ $post->published_at?->format('j M Y') }}
                    </time>
                </p>
            </header>

            @if ($post->cover_url)
                <img
                    src="{{ $post->cover_url }}"
                    alt="Visual for {{ $post->title }}"
                    class="w-full aspect-[16/8] object-cover rounded-2xl mb-10"
                    width="1200"
                    height="630"
                    fetchpriority="high"
                    decoding="async"
                >
            @endif

            {{-- `prose-wx` rather than `prose`: the Tailwind typography plugin
                 is not installed, so `prose` resolved to nothing and this body
                 rendered as an unstyled wall of text. --}}
            <div class="prose-wx max-w-none">
                {!! $post->bodyHtml !!}
            </div>

            <footer class="mt-14 pt-8 border-t border-gray-100">
                <a href="{{ route('insights.index') }}" class="btn btn-ghost rounded-full px-6">All insights</a>
            </footer>

        </div>
    </article>
</x-layouts.app>

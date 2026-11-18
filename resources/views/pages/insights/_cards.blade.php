@foreach ($posts as $post)
    {{-- tabindex="-1" only so the "Load more" handler can park focus on a
     heading after appending this batch. It is not in the tab order, so it does
     not add a stop for keyboard users. --}}
    <article class="flex flex-col">
        @if ($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="" class="w-full aspect-[16/9] object-cover rounded-xl mb-5" width="800" height="450" loading="lazy" decoding="async">
        @endif
        @if ($post->category)
            <p class="label-sm mb-2">{{ $post->category->name }}</p>
        @endif
        <h2 tabindex="-1" class="text-xl font-bold text-navy leading-snug mb-2">
            <a href="{{ route('insights.show', $post->slug) }}" class="hover:text-red-600 transition-colors">{{ $post->title }}</a>
        </h2>
        <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ $post->excerpt }}</p>
        <time class="text-xs text-gray-600 mt-auto" datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
    </article>
@endforeach

{{--
    GENERIC PAGE — the template for a page an editor created in the admin.

    This is what makes the CMS real: a page record with no bespoke Blade
    template of its own still renders. It is assembled from three parts, in
    this order:

      1. the page hero      — eyebrow / H1 / intro, from the page row
      2. the page body      — long-form rich text, from the page row
      3. the page's blocks  — in the order the editor arranged them

    Deliberately not used by the bespoke pages (work, services, about,
    contact, insights). Those have designed layouts that this template would
    flatten; they get their copy from blocks through PageBlocks::byKey()
    instead, and their URL from their own route.

    A page that has none of the three still renders its hero rather than a
    blank page, so a half-finished draft is visible to its author instead of
    looking broken.
--}}

<x-layouts.app
    :title="$page->title"
    :seoTitle="$page->seo_title ?: $page->title"
    :seoDescription="$page->seo_description ?: Str::limit(strip_tags((string) $page->intro), 160)"
    :ogTitle="$page->seo_title ?: $page->title"
    :seoCanonical="$page->url"
    ogType="website"
>
    <x-page-hero :page="$page" />

    @if (filled($page->bodyHtml))
        <section class="pb-8">
            <div class="container-wx">
                <div class="prose-wx max-w-3xl">{!! $page->bodyHtml !!}</div>
            </div>
        </section>
    @endif

    @foreach ($page->blocksInOrder() as $block)
        @include('sections.blocks', ['section' => $block, 'key' => $block->key])
    @endforeach
</x-layouts.app>

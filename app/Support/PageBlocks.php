<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Collection;

/**
 * Resolves a page's blocks for a template.
 *
 * The homepage shipped its own private `blocksByKey()` and every other template
 * had none, which is why only the homepage could ever be edited. Templates now
 * ask for the blocks they render instead of each one deciding how to look them
 * up, so a block added in the admin reaches every page that renders it.
 *
 * Blocks are addressed by `key` rather than by position, because that is what
 * makes promotion safe: the template says "give me the `who-we-are` block" and
 * gets the editor's copy for that section, or nothing, in which case the
 * template's shipped markup is used unchanged. Adding, removing or reordering
 * blocks in the admin can therefore never re-point one section's copy at
 * another, which is the failure mode of storing blocks in a repeater on the
 * page form.
 */
final class PageBlocks
{
    /**
     * Blocks for a page, keyed by block key, in display order.
     *
     * Blank keys are dropped: they are the only value a template can key on,
     * and a block without one can never be found.
     *
     * @return Collection<string, PageSection>
     */
    public static function byKey(?Page $page): Collection
    {
        if ($page === null) {
            return new Collection;
        }

        return $page->sections
            ->filter(fn (PageSection $section): bool => filled($section->key))
            ->keyBy('key');
    }

    /**
     * A page's blocks in display order, with nothing dropped.
     *
     * This is what a page assembled from blocks renders: every block on the
     * page, in the order the editor arranged them. Templates that already have
     * a fixed layout use byKey() instead and name the blocks they need.
     *
     * @return Collection<int, PageSection>
     */
    public static function ordered(?Page $page): Collection
    {
        if ($page === null) {
            return new Collection;
        }

        return $page->sections
            ->filter(fn (PageSection $section): bool => filled($section->type))
            ->values();
    }

    /**
     * Blocks of a published page, by slug.
     *
     * @return Collection<string, PageSection>
     */
    public static function forSlug(string $slug): Collection
    {
        return self::byKey(self::page($slug));
    }

    public static function page(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->where('is_published', true)->first();
    }
}

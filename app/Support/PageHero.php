<?php

namespace App\Support;

use App\Models\Page;

/**
 * Hero copy for a page that has a bespoke design.
 *
 * The bespoke pages (about, services, work, industries) are hand-designed
 * templates, and they should stay that way — flattening them into blocks would
 * throw away the layouts. But their opening statement was hardcoded in Blade,
 * which made the single most important sentence on each page the one thing no
 * editor could change.
 *
 * This resolves that sentence from the page record when one exists, and falls
 * back to the value already in the template when one does not. The fallback is
 * what makes the change safe to deploy on its own: a site whose pages have not
 * been created yet renders exactly as it did before, and an editor creates the
 * page record to take over the copy.
 */
final class PageHero
{
    /**
     * Hero copy for a slug, falling back to the template's own copy.
     *
     * @param  array{eyebrow?: string, headline?: string, intro?: string}  $fallback
     * @return array{eyebrow: string, headline: string, intro: string}
     */
    public static function for(string $slug, array $fallback = []): array
    {
        $page = self::page($slug);

        return [
            'eyebrow' => (string) ($page?->eyebrow ?: ($fallback['eyebrow'] ?? '')),
            'headline' => (string) ($page?->heading ?: ($fallback['headline'] ?? '')),
            'intro' => (string) ($page?->intro ?: ($fallback['intro'] ?? '')),
        ];
    }

    /**
     * A single page, read at most once per request.
     *
     * Cached per slug because a layout and its page can both ask for the same
     * hero, and this is on the critical path of every public request.
     */
    private static function page(string $slug): ?Page
    {
        return self::$cache[$slug] ??= Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();
    }

    /**
     * @var array<string, Page|null>
     */
    private static array $cache = [];
}

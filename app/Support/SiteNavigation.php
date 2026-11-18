<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The site navigation, built once for the header and the footer.
 *
 * Navigation was a hardcoded array in the header partial, which meant a page an
 * editor created in the admin could never appear in the menu and the menu
 * could not be reordered without a code change. It is now assembled from two
 * sources:
 *
 *   1. The curated core links, which are the designed information architecture
 *      and stay put. These are the sections the business is organised around,
 *      and their order is a design decision, not a content one.
 *   2. Everything else flagged show_in_nav on a published page, in the order
 *      an editor set with nav_position.
 *
 * The two are merged without duplicating: a page that already has a core link
 * (about, contact) is not added a second time, it is the core link. A page
 * that does not — a new legal page, a careers page — is appended.
 *
 * Only published pages qualify, and a page with no resolvable route is dropped
 * rather than rendered as a dead link, so a half-configured page cannot put a
 * 404 in the menu.
 */
final class SiteNavigation
{
    /**
     * The designed information architecture, in order.
     *
     * @return list<array{label: string, route: string, pattern: string}>
     */
    private static function core(): array
    {
        return [
            ['label' => 'Work', 'route' => 'work.index', 'pattern' => 'work.*'],
            ['label' => 'Services', 'route' => 'services.index', 'pattern' => 'services.*'],
            ['label' => 'About', 'route' => 'about', 'pattern' => 'about'],
            ['label' => 'Insights', 'route' => 'insights.index', 'pattern' => 'insights.*'],
            ['label' => 'Contact', 'route' => 'contact', 'pattern' => 'contact'],
        ];
    }

    /**
     * Every navigation item, in order, with its active state resolved.
     *
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    public static function items(): Collection
    {
        $items = self::coreItems()
            ->concat(self::pageItems(self::coveredSlugs()));

        return $items->values();
    }

    /**
     * The mobile menu, which leads with Home.
     *
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    public static function mobileItems(): Collection
    {
        if (Route::has('home')) {
            return self::items()->prepend([
                'label' => 'Home',
                'href' => route('home'),
                'active' => request()->routeIs('home'),
            ]);
        }

        return self::items();
    }

    /**
     * Pages that belong in the footer only: published and routable, but not
     * already in the primary navigation and not the unconditional legal links.
     *
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    public static function footerItems(): Collection
    {
        return self::pageItems(self::coveredSlugs()->merge(self::legalSlugs()));
    }

    /**
     * The legal pages the footer always links to.
     *
     * Separate from footerItems() because these are a compliance obligation
     * rather than an editorial choice: they render whether or not an editor has
     * flagged them for navigation, and footerItems() excludes them so a page
     * that is both legal and flagged does not appear twice.
     *
     * A legal page is only linked when it is actually published. Linking a
     * draft policy would 404 the visitor, which is a worse outcome than not
     * linking it.
     *
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    public static function legalItems(): Collection
    {
        return Page::query()
            ->whereIn('slug', self::legalSlugs())
            ->where('is_published', true)
            ->orderBy('id')
            ->get()
            ->map(fn (Page $page): array => [
                'label' => $page->nav_text,
                'href' => $page->url,
                'active' => self::isActive($page),
            ]);
    }

    /**
     * @return Collection<int, string>
     */
    private static function legalSlugs(): Collection
    {
        return collect(['privacy', 'terms']);
    }

    /**
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    private static function coreItems(): Collection
    {
        return collect(self::core())
            ->filter(fn (array $link): bool => Route::has($link['route']))
            ->map(fn (array $link): array => [
                'label' => $link['label'],
                'href' => route($link['route']),
                'active' => request()->routeIs($link['pattern']),
            ]);
    }

    /**
     * Editor-managed navigation entries.
     *
     * @param  Collection<int, string>  $covered
     * @return Collection<int, array{label: string, href: string, active: bool}>
     */
    private static function pageItems(Collection $covered): Collection
    {
        return Page::query()
            ->inNavigation()
            ->get()
            ->reject(fn (Page $page): bool => $covered->contains($page->slug))
            ->map(fn (Page $page): array => [
                'label' => $page->nav_text,
                'href' => $page->url,
                'active' => self::isActive($page),
            ]);
    }

    /**
     * Whether the current request is this page.
     *
     * Two cases, because a page can be served by its own route or by the
     * catch-all: the route name match covers a bespoke route, and the slug
     * match covers the catch-all. Checking the path as well means a page
     * served at /work highlights while a project detail page is open, which is
     * what a visitor expects from a menu.
     */
    private static function isActive(Page $page): bool
    {
        $current = request()->path();
        $target = ltrim(parse_url($page->url, PHP_URL_PATH) ?? '', '/');

        return $current === $target
            || Str::startsWith($current.'/', $target.'/');
    }

    /**
     * Slugs the core links already account for.
     *
     * Derived from the routes themselves rather than hardcoded a second time,
     * so renaming a core route cannot leave a duplicate menu entry behind.
     *
     * @return Collection<int, string>
     */
    private static function coveredSlugs(): Collection
    {
        return collect(self::core())
            ->map(fn (array $link): string => explode('.', $link['route'])[0])
            ->filter()
            ->values();
    }
}

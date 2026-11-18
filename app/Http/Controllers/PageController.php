<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\PageBlocks;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renders CMS page records publicly.
 *
 * Two jobs, and the difference matters:
 *
 *  - Pages with a route of their own (privacy, terms) arrive here by an
 *    explicit route and are rendered by their own template.
 *  - Pages with no route are reached by the catch-all route at the end of
 *    web.php and rendered by the generic template, from their own hero, body
 *    and blocks. This is what makes "create a page in the admin" mean
 *    something: without it a new page record renders nowhere.
 *
 * Publish state is enforced here for both, so an unpublished page 404s rather
 * than leaking its copy to anyone who guesses the URL.
 */
class PageController extends Controller
{
    /**
     * A page served by a route named in web.php (privacy, terms).
     */
    public function show(string $slug, Request $request): View
    {
        $page = $this->publishedPage($slug);

        return view('pages.legal.show', [
            'page' => $page,
            'title' => $page->title,
            'seoTitle' => $page->seo_title,
            'seoDescription' => $page->seo_description,
        ]);
    }

    /**
     * A page with no route of its own, reached by the catch-all.
     *
     * Guarded on the slug rather than on the route list, so a page cannot be
     * shadowed by, or shadow, a bespoke route: a slug that a real route already
     * serves is not served twice.
     */
    public function generic(string $slug, Request $request): View
    {
        $page = $this->publishedPage($slug);

        return view('pages.generic', [
            'page' => $page,
            'blocks' => PageBlocks::byKey($page),
        ]);
    }

    /**
     * Preview a page, published or not.
     *
     * The draft is the point: an editor needs to see a page before it goes
     * live, which means this must not filter on is_published. Access is
     * controlled by the `auth` + `admin` middleware on the route, not by the
     * record's state, so a draft is never publicly readable.
     */
    public function preview(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.generic', [
            'page' => $page,
            'blocks' => PageBlocks::byKey($page),
            'isPreview' => true,
        ]);
    }

    /**
     * A published page, or a 404.
     *
     * firstOrFail on the published scope is what makes the publish flag real
     * for pages that have no bespoke route behind them.
     */
    private function publishedPage(string $slug): Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }
}

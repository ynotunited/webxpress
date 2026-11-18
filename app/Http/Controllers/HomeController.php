<?php

namespace App\Http\Controllers;

use App\Models\ClientReview;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index()
    {
        // Bounded: the pager reads every card it is handed into the HTML, so this
        // caps the homepage weight as the table grows. Twelve is three full
        // sets of the four the section shows at a time, which is enough for
        // the controls to be worth having without the page getting long.
        $reviews = ClientReview::forDisplay(12)->get();

        $posts = Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        // Editable page blocks, keyed by block key. Each section view falls back
        // to its shipped Blade markup when a block is absent or unrecognised,
        // so a missing block never breaks the homepage.
        $page = Page::query()->where('slug', 'home')->first();

        return view('pages.home', [
            'reviews' => $reviews,
            'posts' => $posts,
            'blocks' => $this->blocksByKey($page),
            'page' => $page,
        ]);
    }

    /**
     * @return Collection<string, PageSection>
     */
    private function blocksByKey(?Page $page): Collection
    {
        if ($page === null) {
            return collect();
        }

        return $page->sections
            ->filter(fn ($section): bool => $section->type !== null && $section->key !== null)
            ->keyBy('key');
    }
}

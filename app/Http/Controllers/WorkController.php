<?php

namespace App\Http\Controllers;

use App\Models\ClientReview;
use App\Models\Page;
use App\Services\ProjectCatalog;
use App\Services\ServiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        // Resolve both selections against the unfiltered catalogue first, so an
        // empty combination still shows as "no results" instead of quietly
        // widening back to everything.
        $activeCategory = $this->validFacet(ProjectCatalog::categories(), $request->query('category'));
        $activeIndustry = $this->validFacet(ProjectCatalog::industries(), $request->query('industry'));

        return view('pages.work.index', [
            'projects' => ProjectCatalog::numbered($activeCategory, $activeIndustry),
            'categories' => ProjectCatalog::categories($activeIndustry),
            'industries' => ProjectCatalog::industries($activeCategory),
            'activeCategory' => $activeCategory,
            'activeIndustry' => $activeIndustry,
            // What each filter row's "All" chip will return, given the other
            // row's selection.
            'allCounts' => [
                'category' => count(ProjectCatalog::numbered(null, $activeIndustry)),
                'industry' => count(ProjectCatalog::numbered($activeCategory, null)),
            ],
            'total' => ProjectCatalog::count(),
            'reviews' => ClientReview::forDisplay(3)->get(),
            'page' => Page::query()->where('slug', 'work')->where('is_published', true)->first(),
        ]);
    }

    /**
     * The slug of the requested facet, or null when it is absent or unknown.
     *
     * @param  array<int, array{name: string, slug: string, count: int}>  $facets
     */
    private function validFacet(array $facets, mixed $requested): ?string
    {
        return collect($facets)->firstWhere('slug', (string) $requested)['slug'] ?? null;
    }

    public function show(string $slug)
    {
        $project = ProjectCatalog::get($slug);

        // An unknown slug must 404 rather than return 200 with a stub body,
        // which would let search engines index an unbounded set of thin
        // duplicate URLs.
        if ($project === null) {
            throw new NotFoundHttpException;
        }

        // Resolve service slugs to their display names once, so the page never
        // renders a chip pointing at a service that does not exist.
        $services = collect($project['services'])
            ->map(fn (string $serviceSlug): array => [
                'slug' => $serviceSlug,
                'name' => ServiceCatalog::get($serviceSlug)['name'] ?? Str::headline($serviceSlug),
            ])
            ->values()
            ->all();

        return view('pages.work.show', [
            'project' => $project,
            'number' => ProjectCatalog::numberFor($slug),
            'services' => $services,
            'related' => ProjectCatalog::related($slug, 3),
        ]);
    }
}

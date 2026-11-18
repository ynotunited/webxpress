<?php

namespace Tests\Feature\Public;

use App\Models\ClientReview;
use App\Services\ProjectCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Work index and project detail pages.
 *
 * The portfolio is a static catalogue, which makes two classes of bug possible
 * and invisible in review: a project that is listed but never described, and a
 * link that points at a project or service that does not exist. Both are
 * asserted here, because a portfolio full of dead links is worse than a short
 * one.
 */
class WorkPageTest extends TestCase
{
    use RefreshDatabase;

    public static function projectProvider(): array
    {
        return array_map(
            fn (string $slug): array => [$slug],
            array_keys(ProjectCatalog::all())
        );
    }

    public function test_index_lists_every_catalogued_project(): void
    {
        $html = $this->get('/work')->assertOk()->getContent();

        foreach (ProjectCatalog::all() as $project) {
            $this->assertStringContainsString(e($project['name']), $html, "{$project['slug']} missing from /work");
        }
    }

    public function test_index_offers_a_link_for_every_discipline(): void
    {
        $html = $this->get('/work')->assertOk()->getContent();

        foreach (ProjectCatalog::categories() as $category) {
            $this->assertStringContainsString(
                e(route('work.index', ['category' => $category['slug']])),
                $html,
                "no filter link for {$category['slug']}"
            );
        }
    }

    public function test_category_filter_shows_exactly_its_own_projects(): void
    {
        $catalogue = ProjectCatalog::all();

        foreach (ProjectCatalog::categories() as $facet) {
            $html = $this->get('/work?category='.$facet['slug'])->assertOk()->getContent();

            foreach ($catalogue as $project) {
                $inside = Str::slug($project['category']) === $facet['slug'];

                // Names are escaped because the assertion runs against raw HTML:
                // a client called "7&8 Specialist Hospital" renders as
                // 7&amp;8 and would never match unescaped.
                if ($inside) {
                    $this->assertStringContainsString(
                        e($project['name']),
                        $html,
                        "{$project['name']} is in {$facet['slug']} but missing"
                    );
                } else {
                    $this->assertStringNotContainsString(
                        e($project['name']),
                        $html,
                        "{$project['name']} is not in {$facet['slug']} but appeared"
                    );
                }
            }
        }
    }

    public function test_unknown_category_filter_falls_back_to_the_full_list(): void
    {
        // A stale or hand-edited query value must degrade to the full grid
        // rather than serve an empty page.
        $html = $this->get('/work?category=not-a-discipline')->assertOk()->getContent();

        foreach (ProjectCatalog::all() as $project) {
            $this->assertStringContainsString(
                e($project['name']),
                $html,
                "{$project['name']} missing from the fallback grid"
            );
        }
    }

    public function test_industry_filter_narrows_the_grid(): void
    {
        [$slug, $expected, $absent] = $this->isolatingFilter('industry');

        $html = $this->get('/work?industry='.$slug)->assertOk()->getContent();

        $this->assertStringContainsString(e($expected), $html);
        $this->assertStringNotContainsString(e($absent), $html);
    }

    public function test_filters_combine(): void
    {
        // Combining must intersect, not replace: a project has to survive both
        // filters at once. And a sibling sharing the category but not the
        // industry has to drop out, which is what proves the second filter is
        // doing something rather than riding along on the first.
        $catalogue = ProjectCatalog::all();
        $project = reset($catalogue);
        $category = Str::slug($project['category']);
        $industry = Str::slug($project['industry']);

        $sibling = collect($catalogue)->first(
            fn (array $other): bool => Str::slug($other['category']) === $category
                && Str::slug($other['industry']) !== $industry
        );

        $html = $this->get("/work?category={$category}&industry={$industry}")->assertOk()->getContent();

        $this->assertStringContainsString(e($project['name']), $html);

        if ($sibling !== null) {
            $this->assertStringNotContainsString(
                e($sibling['name']),
                $html,
                'a project outside the industry still survived both filters'
            );
        }
    }

    public function test_impossible_filter_combination_says_so_instead_of_showing_nothing(): void
    {
        // Derived, not hardcoded: a fixed pair stops being impossible the moment
        // the catalogue grows a project that matches it, and the test then
        // passes for the wrong reason or fails confusingly.
        $impossible = $this->impossibleFilterPair();

        if ($impossible === null) {
            $this->markTestSkipped(
                'No real category/industry pair is empty in the current catalogue, so the '
                .'empty state is unreachable. It becomes testable again once the portfolio '
                .'spans more than one category.'
            );
        }

        $html = $this->get("/work?category={$impossible[0]}&industry={$impossible[1]}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('No projects match that combination', $html);
        $this->assertStringContainsString('Clear filters', $html);
    }

    /**
     * A filter value that isolates exactly one project, with a bystander to
     * prove it narrowed.
     *
     * Expectations are read from the catalogue rather than hardcoded. The
     * catalogue deliberately replaces a placeholder entry with the real client
     * name once a project ships screenshots, so a hardcoded name here goes
     * stale the moment the portfolio content changes — and it had: these tests
     * were asserting placeholder names for projects since renamed, so they
     * failed while the filtering itself was fine.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function isolatingFilter(string $field): array
    {
        $facets = $field === 'category'
            ? ProjectCatalog::categories()
            : ProjectCatalog::industries();

        $catalogue = ProjectCatalog::all();

        foreach ($facets as $facet) {
            if ($facet['count'] !== 1) {
                continue;
            }

            $inside = collect($catalogue)->first(
                fn (array $project): bool => Str::slug($project[$field]) === $facet['slug']
            );

            $absent = collect($catalogue)->reject(
                fn (array $project): bool => Str::slug($project[$field]) === $facet['slug']
                    || $project['name'] === $inside['name']
            )->first();

            if ($absent !== null) {
                return [$facet['slug'], $inside['name'], $absent['name']];
            }
        }

        $this->fail("No {$field} isolates a single project, so this test cannot discriminate a filtered grid.");
    }

    /**
     * The first real category/industry pair that no project satisfies, or null
     * when the catalogue has no such pair.
     *
     * @return array{0: string, 1: string}|null
     */
    private function impossibleFilterPair(): ?array
    {
        $catalogue = ProjectCatalog::all();

        foreach (ProjectCatalog::categories() as $category) {
            foreach (ProjectCatalog::industries() as $industry) {
                $matches = array_filter(
                    $catalogue,
                    fn (array $project): bool => Str::slug($project['category']) === $category['slug']
                        && Str::slug($project['industry']) === $industry['slug']
                );

                if ($matches === []) {
                    return [$category['slug'], $industry['slug']];
                }
            }
        }

        return null;
    }

    public function test_filtered_views_canonicalise_to_the_full_portfolio(): void
    {
        // Filter permutations are the same page. Letting them index separately
        // would multiply thin duplicates of one list.
        $html = $this->get('/work?category=saas-fintech')->assertOk()->getContent();

        $this->assertStringContainsString('rel="canonical" href="'.e(route('work.index')).'"', $html);
    }

    #[DataProvider('projectProvider')]
    public function test_project_page_renders_its_own_content(string $slug): void
    {
        $project = ProjectCatalog::get($slug);

        $html = $this->get("/work/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString(e($project['name']), $html);
        $this->assertStringContainsString(e($project['client']), $html);
        $this->assertStringContainsString(e($project['tagline']), $html);
        $this->assertStringContainsString(e($project['challenge'][0]), $html);
    }

    #[DataProvider('projectProvider')]
    public function test_project_page_renders_its_result_figures_and_stack(string $slug): void
    {
        $project = ProjectCatalog::get($slug);

        $html = $this->get("/work/{$slug}")->assertOk()->getContent();

        foreach ($project['stats'] as $stat) {
            $this->assertStringContainsString(e($stat['value']), $html, "{$slug} is missing a result figure");
            $this->assertStringContainsString(e($stat['label']), $html, "{$slug} is missing a result label");
        }

        foreach ($project['technology'] as $item) {
            $this->assertStringContainsString(e($item), $html, "{$slug} is missing a technology item");
        }
    }

    #[DataProvider('projectProvider')]
    public function test_project_page_links_only_to_existing_work_and_service_pages(string $slug): void
    {
        $html = $this->get("/work/{$slug}")->assertOk()->getContent();

        preg_match_all('#href="([^"]+)"#', $html, $matches);

        // Only the links this page owns: other internal links (legal pages,
        // contact) are CMS-backed and covered by their own tests.
        $owned = array_filter(
            array_unique($matches[1]),
            fn (string $href): bool => str_contains($href, '/work')
                || str_contains($href, '/services')
        );

        $this->assertNotEmpty($owned, "{$slug} links to no work or service pages");

        foreach ($owned as $href) {
            $this->get($href)->assertOk("{$slug} links to a dead page: {$href}");
        }
    }

    public function test_every_catalogued_project_is_reachable_from_the_homepage(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#href="([^"]*/work[^"]*)"#', $html, $matches);

        $links = array_values(array_unique($matches[1]));

        $this->assertNotEmpty($links, 'homepage links to no work pages');

        foreach ($links as $link) {
            $this->get($link)->assertOk("homepage links to a dead work page: {$link}");
        }
    }

    public function test_index_shows_published_reviews_only(): void
    {
        ClientReview::create([
            'client_name' => 'Visible Reference',
            'quote' => 'A published review.',
            'is_published' => true,
        ]);
        ClientReview::create([
            'client_name' => 'Hidden Reference',
            'quote' => 'An unpublished review.',
            'is_published' => false,
        ]);

        $html = $this->get('/work')->assertOk()->getContent();

        $this->assertStringContainsString('Visible Reference', $html);
        $this->assertStringNotContainsString('Hidden Reference', $html);
    }
}

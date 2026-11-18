<?php

namespace App\Models;

use App\Support\Html;
use App\Support\PageBlocks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * A page the site can serve.
 *
 * A page is addressable by `slug` and editable from Admin → Site → Site Pages.
 * Two things make it a real page rather than a metadata stub:
 *
 *  - It has its own hero (`eyebrow`, `headline`, `intro`), so the one thing
 *    every page needs does not have to live in a bespoke Blade template.
 *  - It has blocks, which either supply copy to a fixed design template
 *    (byKey) or compose the page outright (ordered). See PageBlocks.
 *
 * A page with a route of its own (work, services, contact) is served by that
 * route's controller and template; this model is where its SEO, publish state
 * and blocks live. A page with no route is served by the catch-all
 * PageController route and rendered from its own blocks.
 */
class Page extends Model
{
    protected static $recordTitleAttribute = 'title';

    protected $fillable = [
        'slug',
        'title',
        'eyebrow',
        'headline',
        'intro',
        'hero_image',
        'content',
        'structured_content',
        'seo_title',
        'seo_description',
        'is_published',
        'show_in_nav',
        'nav_label',
        'nav_position',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_nav' => 'boolean',
        'nav_position' => 'integer',
        'structured_content' => 'array',
    ];

    /**
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->ordered();
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Pages that belong in the site navigation, in the order an editor set.
     *
     * A page with no route of its own is reachable through the catch-all, so
     * it qualifies; a page whose slug is claimed by a bespoke route still
     * resolves through that route, so it qualifies too.
     *
     * @param  Builder<Page>  $query
     */
    public function scopeInNavigation(Builder $query): void
    {
        $query->where('show_in_nav', true)
            ->where('is_published', true)
            ->orderBy('nav_position')
            ->orderBy('id');
    }

    public static function getModelLabel(): string
    {
        return 'Page';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pages';
    }

    /**
     * The page's rich text body, sanitised for output.
     *
     * Read through the model rather than off the row so a page body cannot be
     * echoed raw from one template and cleaned in another.
     */
    public function getBodyHtmlAttribute(): string
    {
        return Html::sanitize($this->content ?? '');
    }

    /**
     * @deprecated Use $page->bodyHtml instead. The old name read as if it
     *             were already-safe HTML, which is what this model exists to
     *             stop anyone assuming.
     */
    public function getContentHtmlAttribute(): string
    {
        return $this->bodyHtml;
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return Html::assetUrl($this->hero_image);
    }

    /**
     * The H1. Falls back to the title so a page created before the hero fields
     * existed still renders a heading.
     */
    public function getHeadingAttribute(): string
    {
        return $this->headline ?: $this->title;
    }

    public function getNavTextAttribute(): string
    {
        return $this->nav_label ?: $this->title;
    }

    /**
     * The public URL for this page.
     *
     * Resolved from a real route when one exists, so a page that is served by a
     * bespoke controller links to that controller's URL rather than to a
     * catch-all path that would 404. A page with no route is served by the
     * catch-all, which is exactly `/{slug}`.
     */
    public function getUrlAttribute(): string
    {
        foreach ($this->routeNames() as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }

        return url('/'.$this->slug);
    }

    /**
     * Route names that could serve this page, best first.
     *
     * The slug itself is included so the bespoke pages that named their route
     * after the slug (about, privacy, terms, contact) resolve directly, and
     * the block-scoped names cover the rest (work, services, insights).
     *
     * @return list<string>
     */
    public function routeNames(): array
    {
        $segment = Str::before($this->slug, '/');

        $candidates = array_filter([
            $segment,
            $segment.'.index',
            'page.'.$segment,
        ]);

        return array_values(array_unique($candidates));
    }

    /**
     * Whether this page has a route that serves it, as opposed to being
     * served by the catch-all.
     */
    public function hasDedicatedRoute(): bool
    {
        foreach ($this->routeNames() as $name) {
            if (Route::has($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This page's blocks, keyed by block key.
     *
     * @return Collection<string, PageSection>
     */
    public function blocksByKey(): Collection
    {
        return PageBlocks::byKey($this);
    }

    /**
     * This page's blocks in the order an editor arranged them.
     *
     * @return Collection<int, PageSection>
     */
    public function blocksInOrder(): Collection
    {
        return PageBlocks::ordered($this);
    }

    /**
     * Whether this page has anything to render a body from.
     */
    public function hasContent(): bool
    {
        return filled($this->content) || $this->sections->isNotEmpty();
    }

    /**
     * Clean the body on the way in, as well as on the way out.
     *
     * The read accessor is the important half, but sanitising only there means
     * the raw value stays in the database and in the model, so anything that
     * reaches for `content` instead of `bodyHtml` — a future export, an API
     * resource, a console command — gets unsanitised markup. Storing it clean
     * means there is only one kind of HTML in this column.
     *
     * Sanitising is idempotent, so re-saving an untouched page is a no-op.
     */
    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            if ($page->isDirty('content') && filled($page->content)) {
                $page->content = Html::sanitize($page->content);
            }
        });
    }
}

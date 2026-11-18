<?php

namespace App\Models;

use App\Services\ProjectCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientReview extends Model
{
    protected $fillable = [
        'client_name',
        'client_role',
        'company',
        'project_slug',
        'service',
        'quote',
        'outcome',
        'rating',
        'sort_order',
        'is_featured',
        'is_published',
    ];

    protected $casts = [
        'rating' => 'integer',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public static function getModelLabel(): string
    {
        return 'Client Review';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Client Reviews';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
 * Published reviews in display order, bounded.
     *
     * The order is fixed here rather than in each controller so every page
     * shows the same reviews in the same sequence.
     *
     * Still bounded, though the reason changed. The section used to build a
     * scrolling marquee by rendering the list twice, so every row added in the
     * admin cost two cards in the markup of three pages. It is paged now and
     * each card is rendered once, but the cap still matters: the homepage reads
     * every card it is given and sends them all in the HTML, so an unbounded
     * read would still grow the page in proportion to the table.
     */
    public function scopeForDisplay(Builder $query, int $limit = 6): Builder
    {
        return $query
            ->published()
            ->orderByRaw('is_featured DESC, sort_order ASC, id DESC')
            ->limit($limit);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * The catalogue project this voice describes, or null.
     *
     * The catalogue is a PHP array rather than a table, so this cannot be an
     * Eloquent relation — it resolves the slug by hand and returns null for a
     * slug that no longer exists.
     *
     * @return array<string, mixed>|null
     */
    public function workProject(): ?array
    {
        if ($this->project_slug === null || $this->project_slug === '') {
            return null;
        }

        return ProjectCatalog::get($this->project_slug);
    }

    /**
     * The company to credit, preferring the portfolio over free text.
     *
     * `company` is typed by hand in the admin, so it drifts: it ended up
     * crediting organisations that appear nowhere in the work index, which
     * undercuts the one claim the section is making. When the review points at
     * a project, the name printed is whatever that project is called in the
     * catalogue. `company` remains the fallback so a review with no project —
     * or one whose slug was retired — still renders.
     */
    public function creditedCompany(): string
    {
        $project = $this->workProject();

        if ($project !== null && ! empty($project['name'])) {
            return (string) $project['name'];
        }

        return (string) ($this->company ?? '');
    }
}

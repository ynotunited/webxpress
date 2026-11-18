<?php

namespace App\Models;

use App\Support\Html;
use App\Support\PageSectionSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Validation\ValidationException;

class PageSection extends Model
{
    /**
     * Block types with a dedicated renderer in sections/blocks.
     *
     * A type may also be an "overlay": one that only supplies copy to a shipped
     * section template, which renders the list from the database itself. Those
     * have no renderer of their own and resolve through their block key.
     */
    public const TYPE_BIO = 'bio';

    public const TYPE_LOGO_STRIP = 'logo_strip';

    public const TYPE_STEPS = 'steps';

    public const TYPE_CARD_GRID = 'card_grid';

    public const TYPE_CTA = 'cta';

    public const TYPE_SECTION_HEAD = 'section_head';

    public const TYPE_PROSE = 'prose';

    public const TYPE_FEATURE_SPLIT = 'feature_split';

    public const TYPE_STATS = 'stats';

    public const TYPE_FAQ = 'faq';

    /**
     * Block types whose renderer is optional: they fall back to the section
     * template named by the block's key.
     *
     * @return list<string>
     */
    public const OVERLAY_TYPES = [
        self::TYPE_SECTION_HEAD,
    ];

    protected $fillable = [
        'page_id',
        'type',
        'key',
        'label',
        'sort_order',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return data_get($this->data, $field, $default);
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: \Illuminate\Support\Str::headline((string) $this->key);
    }

    /**
     * Rich text, sanitised for output.
     *
     * Reading HTML through the model rather than through the view is
     * deliberate: it means a block cannot be echoed raw from one template and
     * sanitised in another. The value is escaped by Blade everywhere else, so
     * this is the only path that must clean it itself.
     */
    public function getHtml(string $field): string
    {
        return Html::sanitize((string) $this->get($field, ''));
    }

    public function getImageUrl(string $field = 'image'): ?string
    {
        return Html::assetUrl($this->get($field));
    }

    /**
     * Sanitise markup that is nested inside the data payload (a FAQ answer),
     * where there is no single top-level field to route through getHtml().
     */
    public static function sanitizeHtml(?string $html): string
    {
        return Html::sanitize($html);
    }

    public function getParagraphs(): array
    {
        return array_values(array_filter(
            (array) $this->get('paragraphs', []),
            fn ($paragraph) => is_string($paragraph) && trim($paragraph) !== ''
        ));
    }

    /**
     * Single-item controls (a CTA button) that have at least a label.
     *
     * @return array<string, mixed>
     */
    public function getCta(): array
    {
        $cta = (array) $this->get('cta', []);

        return filled($cta['label'] ?? null) ? $cta : [];
    }

    /**
     * The URL for a route-named link, or null when the route no longer exists.
     *
     * Returning null rather than a broken href is the contract: a renamed
     * route leaves the button unrendered instead of dead.
     */
    public function routeUrl(?string $route): ?string
    {
        if (blank($route) || ! RouteFacade::has($route)) {
            return null;
        }

        return route($route);
    }

    public function getStats(): array
    {
        return array_values(array_filter(
            (array) $this->get('stats', []),
            fn ($stat) => is_array($stat) && filled($stat['value'] ?? null)
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getItems(): array
    {
        return array_values(array_filter(
            (array) $this->get('items', []),
            fn ($item) => is_array($item)
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getButtons(): array
    {
        return array_values(array_filter(
            (array) $this->get('buttons', []),
            fn ($button) => is_array($button) && filled($button['label'] ?? null)
        ));
    }

    /**
     * The bullet points of a block.
     *
     * The list lives under `bullets`, not `items`, which is a trap worth a
     * named accessor: a view that reaches for getItems() here silently gets an
     * empty array and the block renders without its list, with no error
     * anywhere. The model is the one place that knows which control each block
     * type stores its list in.
     *
     * @return list<array<string, mixed>>
     */
    public function getBullets(): array
    {
        return array_values(array_filter(
            (array) $this->get('bullets', []),
            fn ($bullet) => is_array($bullet) && filled($bullet['text'] ?? null)
        ));
    }

    /**
     * Drop any payload key the block type does not own, and normalise string
     * lists. Runs on `saving` rather than as an attribute mutator so that
     * `type` is always known, regardless of the order it was assigned in.
     */
    protected static function booted(): void
    {
        static::saving(function (self $section): void {
            $section->guardKeyIsUniqueOnItsPage();

            if (! PageSectionSchema::isKnown((string) $section->type)) {
                return;
            }

            $section->data = PageSectionSchema::sanitize(
                (string) $section->type,
                is_array($section->data) ? $section->data : [],
            );
        });
    }

    /**
     * A block is addressed by its key within its page - the template looks it
     * up that way - so two blocks sharing a key would silently fight over the
     * same section, one overwriting the other at render time.
     *
     * Enforced here rather than as a form rule so every write path is covered:
     * the admin, the seeders and any future API.
     */
    protected function guardKeyIsUniqueOnItsPage(): void
    {
        if (blank($this->key) || blank($this->page_id)) {
            return;
        }

        $taken = static::query()
            ->where('page_id', $this->page_id)
            ->where('key', $this->key)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'key' => "The page already has a block keyed '{$this->key}'. Block keys must be unique on a page.",
            ]);
        }
    }
}

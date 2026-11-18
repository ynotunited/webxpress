<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Canonical description of every page-section block type.
 *
 * Deliberately free of Filament imports: this is the single source of truth
 * for which data keys a block owns and what they validate against. The admin
 * form, the renderers and the seeders all read from here, so a key can never
 * be accepted by the form but silently dropped by the model (or vice versa).
 *
 * Shape per control:
 *   control : text | textarea | html | image | toggle | item | items
 *   label   : admin-facing label
 *   max     : max length (text / textarea / html / image)
 *   options : allowed values, when a control is a fixed set (admin builds a
 *             select from these; omitted means free text)
 *   item    : child control map, required when control is `item` / `items`
 *   format  : `route` on a text control, which restricts it to real route
 *             names instead of free text
 *
 * `html` is the only control whose stored value is markup rather than text.
 * It is sanitised on save (so the database holds only safe markup) and again
 * in the renderer (so seeded or hand-edited rows are covered too). See Html.
 */
final class PageSectionSchema
{
    /**
     * @return array<string, array{label: string, controls: array<string, array<string, mixed>>}>
     */
    public static function types(): array
    {
        return [
            PageSection::TYPE_BIO => [
                'label' => 'Bio / Who We Are',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'quote' => ['control' => 'textarea', 'label' => 'Pull quote', 'max' => 320],
                    'paragraphs' => ['control' => 'textarea', 'label' => 'Paragraphs (one per line)', 'max' => 4000],
                    'cta' => ['control' => 'item', 'label' => 'Call to action', 'item' => [
                        'label' => ['control' => 'text', 'label' => 'Label', 'max' => 60],
                        'route' => ['control' => 'text', 'label' => 'Route name', 'max' => 60, 'format' => 'route'],
                    ]],
                    'stats' => ['control' => 'items', 'label' => 'Stats', 'item' => [
                        'variant' => [
                            'control' => 'text',
                            'label' => 'Tile style',
                            'max' => 20,
                            'options' => [
                                'plain' => 'Plain',
                                'inverted' => 'Inverted (navy)',
                                'dashed' => 'Dashed outline',
                            ],
                        ],
                        'prefix' => ['control' => 'text', 'label' => 'Prefix', 'max' => 20],
                        'value' => ['control' => 'text', 'label' => 'Value', 'max' => 20],
                        'label' => ['control' => 'text', 'label' => 'Label', 'max' => 60],
                    ]],
                ],
            ],

            PageSection::TYPE_LOGO_STRIP => [
                'label' => 'Logo strip / Trusted by',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Leading label', 'max' => 40],
                    'items' => ['control' => 'items', 'label' => 'Clients', 'item' => [
                        'name' => ['control' => 'text', 'label' => 'Client name', 'max' => 80],
                        'abbr' => ['control' => 'text', 'label' => 'Short mark', 'max' => 24],
                    ]],
                ],
            ],

            PageSection::TYPE_STEPS => [
                'label' => 'Numbered process steps',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'items' => ['control' => 'items', 'label' => 'Steps', 'item' => [
                        'number' => ['control' => 'text', 'label' => 'Number', 'max' => 4],
                        'title' => ['control' => 'text', 'label' => 'Title', 'max' => 60],
                        'description' => ['control' => 'textarea', 'label' => 'Description', 'max' => 400],
                    ]],
                ],
            ],

            PageSection::TYPE_CARD_GRID => [
                'label' => 'Card grid',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'subheading' => ['control' => 'textarea', 'label' => 'Subheading', 'max' => 300],
                    'items' => ['control' => 'items', 'label' => 'Cards', 'item' => [
                        'name' => ['control' => 'text', 'label' => 'Title', 'max' => 80],
                        'description' => ['control' => 'textarea', 'label' => 'Description', 'max' => 300],
                    ]],
                ],
            ],

            PageSection::TYPE_CTA => [
                'label' => 'Call to action',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 160],
                    'heading_accent' => ['control' => 'text', 'label' => 'Heading accent (shown in red)', 'max' => 80],
                    'body' => ['control' => 'textarea', 'label' => 'Body', 'max' => 400],
                    'buttons' => ['control' => 'items', 'label' => 'Buttons', 'item' => [
                        'label' => ['control' => 'text', 'label' => 'Label', 'max' => 40],
                        'route' => ['control' => 'text', 'label' => 'Route name', 'max' => 60, 'format' => 'route'],
                        'style' => [
                            'control' => 'text',
                            'label' => 'Style',
                            'max' => 20,
                            'options' => [
                                'primary' => 'Primary (filled)',
                                'ghost' => 'Ghost (outline)',
                            ],
                        ],
                    ]],
                ],
            ],

            PageSection::TYPE_SECTION_HEAD => [
                'label' => 'Section heading (heading only, list comes from the database)',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'subheading' => ['control' => 'textarea', 'label' => 'Subheading', 'max' => 300],
                ],
            ],

            PageSection::TYPE_PROSE => [
                'label' => 'Rich text (long-form body copy)',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'body' => ['control' => 'html', 'label' => 'Body', 'max' => 20000],
                    'align' => [
                        'control' => 'text',
                        'label' => 'Alignment',
                        'max' => 20,
                        'options' => [
                            'left' => 'Left (default)',
                            'center' => 'Centred',
                        ],
                    ],
                ],
            ],

            PageSection::TYPE_FEATURE_SPLIT => [
                'label' => 'Image + text (side by side)',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'body' => ['control' => 'textarea', 'label' => 'Body', 'max' => 600],
                    'image' => ['control' => 'image', 'label' => 'Image', 'max' => 0],
                    'image_alt' => ['control' => 'text', 'label' => 'Image alt text', 'max' => 160],
                    'image_position' => [
                        'control' => 'text',
                        'label' => 'Image side',
                        'max' => 10,
                        'options' => [
                            'left' => 'Left (default)',
                            'right' => 'Right',
                        ],
                    ],
                    'bullets' => ['control' => 'items', 'label' => 'Bullet points', 'item' => [
                        'text' => ['control' => 'text', 'label' => 'Point', 'max' => 160],
                    ]],
                    'cta' => ['control' => 'item', 'label' => 'Call to action', 'item' => [
                        'label' => ['control' => 'text', 'label' => 'Label', 'max' => 60],
                        'route' => ['control' => 'text', 'label' => 'Route name', 'max' => 60, 'format' => 'route'],
                    ]],
                ],
            ],

            PageSection::TYPE_STATS => [
                'label' => 'Stats band (figures across the page)',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'subheading' => ['control' => 'textarea', 'label' => 'Subheading', 'max' => 300],
                    'items' => ['control' => 'items', 'label' => 'Figures', 'item' => [
                        'value' => ['control' => 'text', 'label' => 'Value', 'max' => 20],
                        'label' => ['control' => 'text', 'label' => 'Label', 'max' => 60],
                    ]],
                ],
            ],

            PageSection::TYPE_FAQ => [
                'label' => 'FAQ (question and answer)',
                'controls' => [
                    'label' => ['control' => 'text', 'label' => 'Eyebrow label', 'max' => 60],
                    'heading' => ['control' => 'text', 'label' => 'Heading', 'max' => 120],
                    'items' => ['control' => 'items', 'label' => 'Questions', 'item' => [
                        'question' => ['control' => 'text', 'label' => 'Question', 'max' => 160],
                        'answer' => ['control' => 'html', 'label' => 'Answer', 'max' => 2000],
                    ]],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(
            fn (array $type): string => $type['label'],
            self::types(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function controls(string $type): array
    {
        return self::types()[$type]['controls'] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function keys(string $type): array
    {
        return array_keys(self::controls($type));
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /**
     * Route names an editor can actually put on a public link.
     *
     * A block link names a page ("contact", "work.index"), never a single
     * record, so parameterised routes like work.show are excluded. Only GET
     * routes qualify: linking to a POST endpoint such as contact.store or
     * webhooks.paystack is never what a button means, and listing them in a
     * validation message only invites the mistake. The admin panel is excluded
     * for the same reason - it is not a public destination.
     *
     * @return list<string>
     */
    public static function pageRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->reject(fn (Route $route): bool => $route->parameterNames() !== [] || blank($route->getName()))
            ->filter(fn (Route $route): bool => in_array('GET', $route->methods(), true))
            ->reject(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'filament.'))
            ->map(fn (Route $route): string => (string) $route->getName())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Trim every string in the payload and drop anything the block does not
     * own. A `paragraphs` textarea arrives as one string and is stored as a
     * list, so that shape is normalised here rather than in the model.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(string $type, array $data): array
    {
        $clean = [];

        foreach (self::controls($type) as $key => $control) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($key === 'paragraphs') {
                $clean[$key] = self::toLines($value);

                continue;
            }

            if (($control['control'] ?? null) === 'item') {
                $item = self::sanitizeItem($control['item'] ?? [], $value);

                // A wholly blank single item is dropped rather than stored as
                // an empty map, so the block can tell "unset" from "set".
                if ($item !== []) {
                    $clean[$key] = $item;
                }

                continue;
            }

            if (($control['control'] ?? null) === 'items') {
                $clean[$key] = self::sanitizeItems($control['item'] ?? [], $value);

                continue;
            }

            // Rich text is the one control that is not a plain string. It is
            // sanitised here so the database only ever holds safe markup, and
            // again in the renderer so seeded and hand-edited rows are covered.
            if (($control['control'] ?? null) === 'html') {
                $clean[$key] = Html::sanitize((string) $value);

                continue;
            }

            // An image is a storage path or an absolute URL, not markup. It is
            // resolved to a public URL at render time by Html::assetUrl(), which
            // is what rejects a hostile path; here it is only trimmed.
            if (($control['control'] ?? null) === 'image') {
                $clean[$key] = self::trimTo((string) $value, 255);

                continue;
            }

            if (($control['control'] ?? null) === 'toggle') {
                $clean[$key] = (bool) $value;

                continue;
            }

            $clean[$key] = self::trimTo((string) $value, (int) ($control['max'] ?? 0));
        }

        return $clean;
    }

    /**
     * Normalise a single object control. An unset or wholly blank item
     * normalises to [], which the renderer treats as absent.
     *
     * @param  array<string, array<string, mixed>>  $itemControls
     * @return array<string, mixed>
     */
    private static function sanitizeItem(array $itemControls, mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $item = [];

        foreach ($itemControls as $key => $control) {
            $item[$key] = self::childValue($control, $value[$key] ?? '');
        }

        return collect($item)->filter(fn (mixed $part): bool => $part !== '' && $part !== null)->isNotEmpty()
            ? $item
            : [];
    }

    /**
     * @param  array<string, mixed>  $itemControls
     * @return list<array<string, mixed>>
     */
    private static function sanitizeItems(array $itemControls, mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach (array_values($value) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $item = [];

            foreach ($itemControls as $key => $control) {
                $item[$key] = self::childValue($control, $entry[$key] ?? '');
            }

            // Skip rows the editor left entirely blank.
            if (collect($item)->filter(fn (mixed $part): bool => $part !== '' && $part !== null)->isNotEmpty()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * One value inside a repeater row or a single-item control.
     *
     * Rows can hold rich text of their own (a FAQ answer), so the row helpers
     * sanitise with the same rules the top level does rather than casting
     * everything to a trimmed string.
     *
     * @param  array<string, mixed>  $control
     */
    private static function childValue(array $control, mixed $value): mixed
    {
        return match ($control['control'] ?? 'text') {
            'html' => Html::sanitize((string) $value),
            'image' => self::trimTo((string) $value, 255),
            'toggle' => (bool) $value,
            default => self::trimTo((string) $value, (int) ($control['max'] ?? 0)),
        };
    }

    private static function trimTo(string $value, int $max): string
    {
        $value = trim($value);

        return $max > 0 ? mb_substr($value, 0, $max) : $value;
    }

    /**
     * Accepts either a real list or the single textarea string the admin
     * form submits for it.
     *
     * @return list<string>
     */
    private static function toLines(mixed $value): array
    {
        if (is_array($value)) {
            $lines = array_map('strval', $value);
        } else {
            $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
        }

        $lines = array_map('trim', $lines);

        return array_values(array_filter($lines, fn (string $line): bool => $line !== ''));
    }
}

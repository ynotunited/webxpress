<?php

namespace App\Filament\Resources\Site\Schemas;

use App\Rules\ExistingRouteName;
use App\Support\Concerns\ConfiguresRichTextImages;
use App\Support\PageSectionSchema;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Str;

/**
 * Builds the admin form for one page block from PageSectionSchema.
 *
 * The schema is the single source of truth for what a block owns. This class
 * turns that description into Filament fields, so adding a block type means
 * adding a schema entry and nothing here - and, more importantly, a block can
 * never be edited through a form that is missing one of its fields. That is
 * not a cosmetic risk: PageSection drops payload keys a block does not own on
 * save, so a form missing a field would erase live copy the moment an editor
 * touched an unrelated part of the page.
 *
 * Only the selected block type's fields are ever mounted, and each type
 * declares its own keys, so state paths never collide. See PageSectionEditingTest.
 */
final class PageSectionFields
{
    use ConfiguresRichTextImages;
    /**
     * Fields for a single block type, on the block's stored `data` payload.
     *
     * @return array<int, Component>
     */
    public static function forType(string $type): array
    {
        if (! PageSectionSchema::isKnown($type)) {
            return [];
        }

        $fields = [];

        foreach (PageSectionSchema::controls($type) as $key => $control) {
            $fields[] = self::control("data.{$key}", $control, (string) $key);
        }

        return $fields;
    }

    /**
     * A "Block type" select that reloads the form when the editor changes it.
     */
    public static function typeSelect(): Select
    {
        return Select::make('type')
            ->label('Block type')
            ->options(PageSectionSchema::labels())
            ->required()
            ->live()
            ->native(false)
            ->helperText('Choosing a type loads the fields for that type. The layout stays in the design template.');
    }

    /**
     * @param  array<string, mixed>  $control
     */
    private static function control(string $path, array $control, string $key): Component
    {
        $label = (string) ($control['label'] ?? $key);
        $max = (int) ($control['max'] ?? 0);

        return match ($control['control']) {
            'textarea' => Textarea::make($path)
                ->label($label)
                ->rows($key === 'paragraphs' ? 8 : 3)
                ->maxLength($max ?: null)
                ->columnSpanFull(),

            'html' => self::warnOnRemoteImages(
                self::withLocalImageUploads(RichEditor::make($path), 'page-sections/body'),
            )
                ->label($label)
                ->toolbarButtons([
                    'bold', 'italic', 'link',
                    'h2', 'h3', 'blockquote', 'bulletList', 'orderedList',
                    'attachFiles',
                    'undo', 'redo',
                ])
                ->helperText('Formatting is kept. Scripts and unsafe links are removed when the page is saved. Images must be uploaded with the image button, not linked from another site.')
                ->columnSpanFull(),

            'image' => FileUpload::make($path)
                ->label($label)
                ->image()
                ->disk('public')
                ->directory('page-sections')
                ->visibility('public')
                ->maxSize(4096)
                ->imageEditor()
                ->helperText('Used as-is on the page. Provide a real screenshot or photograph.')
                ->columnSpanFull(),

            'item' => Fieldset::make($label)
                ->schema(self::childFields((array) ($control['item'] ?? []), $path))
                ->columns(2),

            'items' => self::itemList($path, $control, $label),

            default => self::scalar($path, $control, $label, $max),
        };
    }

    /**
     * A repeatable list of child rows (logos, steps, cards, buttons, stats).
     *
     * @param  array<string, mixed>  $control
     */
    private static function itemList(string $path, array $control, string $label): Component
    {
        $child = (array) ($control['item'] ?? []);

        return Repeater::make($path)
            ->label($label)
            ->schema(self::childFields($child))
            ->itemLabel(self::rowLabel(array_keys($child), $label))
            ->addActionLabel('Add '.self::singular($label))
            ->reorderable()
            ->collapsible()
            ->collapsed()
            ->defaultItems(0)
            ->columns(2)
            ->columnSpanFull();
    }

    /**
     * Fields for a child row.
     *
     * Repeater rows are addressed relative to the row, so these are bare key
     * names; a Fieldset lives in the parent state, so those are absolute.
     *
     * @param  array<string, array<string, mixed>>  $children
     * @return array<int, Component>
     */
    private static function childFields(array $children, string $prefix = ''): array
    {
        $fields = [];

        foreach ($children as $key => $control) {
            $fields[] = self::control(
                $prefix === '' ? (string) $key : "{$prefix}.{$key}",
                $control,
                (string) $key,
            );
        }

        return $fields;
    }

    /**
     * A single value: free text, a fixed set of options, or a route name.
     *
     * @param  array<string, mixed>  $control
     */
    private static function scalar(string $path, array $control, string $label, int $max): Component
    {
        if (($control['format'] ?? null) === 'route') {
            return TextInput::make($path)
                ->label($label)
                ->maxLength($max ?: 60)
                ->placeholder('contact')
                ->helperText('A named route. Leave blank to omit the link.')
                ->rule(new ExistingRouteName);
        }

        $options = $control['options'] ?? null;

        if (is_array($options) && $options !== []) {
            return Select::make($path)
                ->label($label)
                ->options($options)
                ->placeholder('Default')
                ->native(false);
        }

        return TextInput::make($path)
            ->label($label)
            ->maxLength($max ?: null);
    }

    /**
     * A row is identified by its first populated value, so a repeater header
     * reads "Interswitch" rather than "Item 1".
     *
     * @param  list<string>  $childKeys
     * @return Closure(array<string, mixed>): string
     */
    private static function rowLabel(array $childKeys, string $label): Closure
    {
        return function (array $state) use ($childKeys, $label): string {
            foreach ($childKeys as $key) {
                if (filled($state[$key] ?? null)) {
                    return Str::limit((string) $state[$key], 40, '');
                }
            }

            return 'New '.self::singular($label);
        };
    }

    private static function singular(string $label): string
    {
        return Str::singular(strtolower($label));
    }
}

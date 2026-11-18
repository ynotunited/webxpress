<?php

namespace App\Filament\Resources\Site;

use App\Filament\Resources\Site\SitePageResource\Pages;
use App\Filament\Resources\Site\PageSectionResource;
use App\Models\Page;
use App\Support\Concerns\ConfiguresRichTextImages;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Editable site pages (Admin → Site → Site Pages).
 *
 * A page is a row: a slug the public site can serve, its hero copy, its body,
 * its SEO, and whether it is live. Its body is either long-form rich text
 * (privacy, terms) or a set of blocks (Site → Page Blocks), or both.
 *
 * Blocks are separate records on purpose. Putting them in a repeater on this
 * form is what made the old page editor unsafe: the form has one schema, so a
 * block whose fields the form did not render was silently emptied on save.
 * PageSectionResource gives every block its own screen with its own fields.
 *
 * @property-read string $url
 */
class SitePageResource extends Resource
{
    use ConfiguresRichTextImages;
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Site Pages';

    protected static ?string $modelLabel = 'Page';

    protected static ?string $pluralModelLabel = 'Pages';

    protected static UnitEnum|string|null $navigationGroup = 'Site';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')
                ->description('Identity and where the page lives.')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Used in the admin and as the page title tag when no SEO title is set.'),

                    TextInput::make('slug')
                        ->label('URL slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null)
                        ->live()
                        ->helperText('Lowercase words separated by hyphens. This is the public path: /your-slug.'),

                    Toggle::make('is_published')
                        ->label('Live')
                        ->helperText('Unpublished pages return 404 on the public site. They are still previewable from the list.')
                        ->inline(false)
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Section::make('Page content')
                ->description('Edit the visible repeated content for this designed page. Changes appear on the site after saving and publishing.')
                ->schema([
                    Repeater::make('structured_content.principles')
                        ->label('About principles')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(80),
                            Textarea::make('body')->required()->rows(3)->maxLength(400),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'about'),

                    Repeater::make('structured_content.process')
                        ->label('About process steps')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(80),
                            Textarea::make('body')->required()->rows(3)->maxLength(400),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'about'),

                    Repeater::make('structured_content.faq')
                        ->label('Services FAQ')
                        ->schema([
                            TextInput::make('question')->required()->maxLength(160),
                            Textarea::make('answer')->required()->rows(3)->maxLength(600),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'services'),

                    FileUpload::make('structured_content.images.hero')
                        ->label('Page hero image')
                        ->image()->disk('public')->directory('pages')->visibility('public')
                        ->helperText('Upload a replacement image. Leave empty to keep the existing design image.')
                        ->visible(fn (Get $get): bool => in_array($get('slug'), ['about', 'services'], true))
                        ->columnSpanFull(),

                    FileUpload::make('structured_content.images.secondary')
                        ->label('Secondary page image')
                        ->image()->disk('public')->directory('pages')->visibility('public')
                        ->helperText('Used by the page’s secondary image slot when the design supports one.')
                        ->visible(fn (Get $get): bool => in_array($get('slug'), ['about', 'services'], true))
                        ->columnSpanFull(),

                    FileUpload::make('structured_content.images.tertiary')
                        ->label('Third page image')
                        ->image()->disk('public')->directory('pages')->visibility('public')
                        ->helperText('Use a different image for the third visual section. Avoid reusing another slot’s image.')
                        ->visible(fn (Get $get): bool => $get('slug') === 'about')
                        ->columnSpanFull(),

                    Repeater::make('structured_content.industry_cards')
                        ->label('Industry cards')
                        ->schema([
                            TextInput::make('name')->label('Industry')->required()->maxLength(80),
                            Textarea::make('description')->required()->rows(3)->maxLength(300),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'industries'),

                    Repeater::make('structured_content.deliverables')
                        ->label('Service deliverables')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(100),
                            Textarea::make('description')->required()->rows(3)->maxLength(400),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => in_array($get('slug'), ['web-development', 'mobile-development', 'branding', 'digital-strategy', 'visuals', 'motion-design', 'seo', 'ai-solutions'], true)),

                    Repeater::make('structured_content.outcomes')
                        ->label('Service outcomes')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(100),
                            Textarea::make('description')->required()->rows(3)->maxLength(400),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => in_array($get('slug'), ['web-development', 'mobile-development', 'branding', 'digital-strategy', 'visuals', 'motion-design', 'seo', 'ai-solutions'], true)),

                    Repeater::make('structured_content.faq')
                        ->label('Service detail FAQ')
                        ->schema([
                            TextInput::make('question')->required()->maxLength(160),
                            Textarea::make('answer')->required()->rows(3)->maxLength(600),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => in_array($get('slug'), ['web-development', 'mobile-development', 'branding', 'digital-strategy', 'visuals', 'motion-design', 'seo', 'ai-solutions'], true)),

                    Repeater::make('structured_content.work_points')
                        ->label('Work page talking points')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(100),
                            Textarea::make('body')->required()->rows(3)->maxLength(400),
                        ])
                        ->columns(2)->reorderable()->collapsible()->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'work'),

                    TextInput::make('structured_content.home_hero_label')
                        ->label('Homepage hero label')
                        ->maxLength(80)
                        ->visible(fn (Get $get): bool => $get('slug') === 'home'),

                    TextInput::make('structured_content.home_hero_heading')
                        ->label('Homepage hero heading')
                        ->maxLength(180)
                        ->visible(fn (Get $get): bool => $get('slug') === 'home'),

                    Textarea::make('structured_content.home_hero_intro')
                        ->label('Homepage hero intro')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('slug') === 'home'),
                ])
                ->collapsible(),

            Section::make('Hero')
                ->description('The block at the top of the page. A page shows its title as the heading if no heading is set.')
                ->columns(2)
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Eyebrow')
                        ->maxLength(60)
                        ->helperText('Small label above the heading.'),

                    TextInput::make('headline')
                        ->label('Heading')
                        ->maxLength(255)
                        ->helperText('The H1. Leave blank to use the title.'),

                    Textarea::make('intro')
                        ->label('Intro')
                        ->maxLength(600)
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Body')
                ->description('Use this for long-form pages such as policies. For designed pages, leave it blank and use Edit blocks from the page list.')
                ->schema([
                    static::warnOnRemoteImages(
                        static::withLocalImageUploads(
                            RichEditor::make('content'),
                            'pages/body',
                        ),
                    )
                        ->toolbarButtons([
                            'bold', 'italic', 'link',
                            'h2', 'h3', 'blockquote', 'bulletList', 'orderedList',
                            'attachFiles',
                            'undo', 'redo',
                        ])
                        ->helperText('Formatting is kept. Scripts and unsafe links are removed when the page is saved. Images must be uploaded with the image button, not linked from another site.')
                        ->columnSpanFull(),
                ]),

            Section::make('Navigation')
                ->description('Where this page appears in the site navigation.')
                ->columns(2)
                ->schema([
                    Toggle::make('show_in_nav')
                        ->label('Show in navigation')
                        ->helperText('Adds the page to the header and footer. A page that already has its own menu entry is not duplicated.')
                        ->inline(false),

                    TextInput::make('nav_label')
                        ->label('Navigation label')
                        ->maxLength(60)
                        ->helperText('Short text for the menu. Leave blank to use the title.')
                        ->visible(fn (Get $get): bool => $get('show_in_nav')),

                    TextInput::make('nav_position')
                        ->label('Menu order')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Lower numbers come first.')
                        ->visible(fn (Get $get): bool => $get('show_in_nav')),
                ]),

            Section::make('SEO')
                ->description('Search and social. Leave blank to inherit the title and intro.')
                ->columns(2)
                ->schema([
                    TextInput::make('seo_title')
                        ->label('SEO title')
                        ->maxLength(70)
                        ->helperText('Aim for 60 characters or fewer.'),

                    TextInput::make('seo_description')
                        ->label('Meta description')
                        ->maxLength(170)
                        ->helperText('Aim for 155 characters or fewer.'),

                    FileUpload::make('hero_image')
                        ->label('Hero image')
                        ->image()
                        ->disk('public')
                        ->directory('page-heroes')
                        ->visibility('public')
                        ->helperText('Upload an image for generic pages. Dedicated pages use their own page image fields above.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Page $record): ?string => $record->slug ? '/'.$record->slug : null),

                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('show_in_nav')
                    ->label('In nav')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sections_count')
                    ->label('Blocks')
                    ->counts('sections')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('title')
            ->filters([
                TernaryFilter::make('is_published')->label('Live only'),
                TernaryFilter::make('show_in_nav')->label('In navigation only'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('blocks')
                    ->label('Edit blocks')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->url(fn (Page $record): string => PageSectionResource::getUrl('index', [
                        'tableFilters' => ['page' => ['value' => $record->getKey()]],
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No pages yet')
            ->emptyStateDescription('Create a page, add blocks to it, then switch it live. Pages without blocks of their own render their hero and body.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSitePages::route('/'),
            'create' => Pages\CreateSitePage::route('/create'),
            'edit' => Pages\EditSitePage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $unpublished = static::getModel()::query()->where('is_published', false)->count();

        return $unpublished > 0 ? (string) $unpublished : null;
    }
}

<?php

namespace App\Filament\Resources\Site;

use App\Filament\Resources\Site\PageSectionResource\Pages\CreatePageSection;
use App\Filament\Resources\Site\PageSectionResource\Pages\EditPageSection;
use App\Filament\Resources\Site\PageSectionResource\Pages\ListPageSections;
use App\Filament\Resources\Site\Schemas\PageSectionFields;
use App\Models\PageSection;
use App\Support\PageSectionSchema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * A page's editable copy, one record per block.
 *
 * Each block type owns a fixed set of keys (PageSectionSchema) and the model
 * drops any key a block does not own, so blocks are edited on their own screen
 * rather than inside the page's own form: a form can then never be missing
 * fields for the block being edited, which would erase live copy on save.
 */
class PageSectionResource extends Resource
{
    protected static ?string $model = PageSection::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Page Blocks';

    protected static ?string $modelLabel = 'Page Block';

    protected static ?string $pluralModelLabel = 'Page Blocks';

    protected static ?string $slug = 'page-blocks';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Block')
                ->description('The key is how the page template finds this block, so it must match the section it fills in.')
                ->schema([
                    Select::make('page_id')
                        ->label('Page')
                        ->relationship('page', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    TextInput::make('key')
                        ->label('Internal key')
                        ->required()
                        ->maxLength(80)
                        ->helperText('e.g. who-we-are, how-we-work, major-cta. Must be unique on the page.'),

                    TextInput::make('label')
                        ->label('Section name')
                        ->maxLength(100)
                        ->helperText('Use a clear name such as “Frequently asked questions” or “Our process”. Leave blank to derive it from the internal key.'),

                    PageSectionFields::typeSelect(),

                    TextInput::make('sort_order')
                        ->label('Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers render first on the page.'),
                ])
                ->columns(2),

            Section::make('Content')
                ->description('The words, links and numbers this block puts on the page. The design itself lives in the template.')
                ->schema(fn (Get $get): array => PageSectionFields::forType((string) $get('type')))
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page.title')
                    ->label('Page')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('display_label')
                    ->label('Section')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('key')
                    ->label('Internal key')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->color('gray'),

                IconColumn::make('data')
                    ->label('Copy')
                    ->boolean()
                    ->getStateUsing(fn (PageSection $record): bool => filled($record->data)),

                TextColumn::make('updated_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('page')->relationship('page', 'title')->searchable()->preload(),
                SelectFilter::make('type')->options(fn (): array => PageSectionSchema::labels()),
            ])
            // Blocks render in sort_order, so the list can be dragged into
            // reading order instead of edited field by field.
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageSections::route('/'),
            'create' => CreatePageSection::route('/create'),
            'edit' => EditPageSection::route('/{record}/edit'),
        ];
    }
}

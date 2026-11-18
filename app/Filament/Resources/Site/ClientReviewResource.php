<?php

namespace App\Filament\Resources\Site;

use App\Filament\Resources\Site\ClientReviewResource\Pages\CreateClientReview;
use App\Filament\Resources\Site\ClientReviewResource\Pages\EditClientReview;
use App\Filament\Resources\Site\ClientReviewResource\Pages\ListClientReviews;
use App\Models\ClientReview;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ClientReviewResource extends Resource
{
    protected static ?string $model = ClientReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Client Reviews';

    protected static ?string $modelLabel = 'Client Review';

    protected static ?string $pluralModelLabel = 'Client Reviews';

    protected static ?string $recordTitleAttribute = 'client_name';

    protected static UnitEnum|string|null $navigationGroup = 'Site';

    protected static ?string $slug = 'client-reviews';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')->schema([
                TextInput::make('client_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('company')
                    ->maxLength(255),
                TextInput::make('client_role')
                    ->label('Role / title')
                    ->maxLength(255),
                Select::make('service')
                    ->options([
                        'Web Development' => 'Web Development',
                        'Mobile Development' => 'Mobile Development',
                        'Branding' => 'Branding',
                        'Digital Strategy' => 'Digital Strategy',
                        'Visuals' => 'Visuals',
                        'Motion Design' => 'Motion Design',
                        'SEO' => 'SEO',
                        'AI Solutions' => 'AI Solutions',
                    ])
                    ->searchable(),
                Textarea::make('quote')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Textarea::make('outcome')
                    ->label('Outcome note')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
            Section::make('Publishing')->schema([
                Select::make('rating')
                    ->options([
                        1 => '1 star',
                        2 => '2 stars',
                        3 => '3 stars',
                        4 => '4 stars',
                        5 => '5 stars',
                    ])
                    ->default(5)
                    ->required(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_featured')
                    ->label('Feature on Services page'),
                Toggle::make('is_published')
                    ->label('Published'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')->searchable()->weight('semibold'),
                TextColumn::make('company')->searchable()->placeholder('No company'),
                TextColumn::make('service')->badge()->color('primary')->placeholder('General'),
                TextColumn::make('rating')->badge()->suffix(' star'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_published')->boolean()->label('Published'),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('updated_at')->dateTime('M j, Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientReviews::route('/'),
            'create' => CreateClientReview::route('/create'),
            'edit' => EditClientReview::route('/{record}/edit'),
        ];
    }
}

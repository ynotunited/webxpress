<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\BlogCategoryResource\Pages\CreateBlogCategory;
use App\Filament\Resources\Blog\BlogCategoryResource\Pages\EditBlogCategory;
use App\Filament\Resources\Blog\BlogCategoryResource\Pages\ListBlogCategories;
use App\Models\Category;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Blog Categories';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Textarea::make('description')
                    ->rows(3),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold'),
                TextColumn::make('posts_count')->counts('posts')->badge()->color('primary'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogCategories::route('/'),
            'create' => CreateBlogCategory::route('/create'),
            'edit' => EditBlogCategory::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Blog\BlogPostResource\Pages;

use App\Filament\Resources\Blog\BlogPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * The list renders a category.name badge on every row. Without this the
     * category is fetched one query at a time, once per post on the page.
     */
    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category');
    }
}

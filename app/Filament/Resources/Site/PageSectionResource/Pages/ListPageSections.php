<?php

namespace App\Filament\Resources\Site\PageSectionResource\Pages;

use App\Filament\Resources\Site\PageSectionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPageSections extends ListRecords
{
    protected static string $resource = PageSectionResource::class;

    /**
     * The list renders page.title on every row. Without this the page is
     * fetched one query at a time, once per block on the page.
     */
    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('page');
    }
}

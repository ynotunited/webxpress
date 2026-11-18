<?php

namespace App\Filament\Resources\Site\ClientReviewResource\Pages;

use App\Filament\Resources\Site\ClientReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientReviews extends ListRecords
{
    protected static string $resource = ClientReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

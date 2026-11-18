<?php

namespace App\Filament\Resources\ProjectInquiries\Pages;

use App\Filament\Resources\ProjectInquiries\ProjectInquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectInquiries extends ListRecords
{
    protected static string $resource = ProjectInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

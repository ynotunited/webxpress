<?php

namespace App\Filament\Resources\ProjectInquiries\Pages;

use App\Filament\Resources\Deals\DealResource;
use App\Filament\Resources\ProjectInquiries\ProjectInquiryResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectInquiry extends ViewRecord
{
    protected static string $resource = ProjectInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDeal')
                ->label('Create deal')
                ->color('info')
                ->url(fn () => DealResource::getUrl('create', ['inquiry_id' => $this->getRecord()])),
            Action::make('conversation')
                ->label('Conversation')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('respond', ['record' => $this->getRecord()])),
            EditAction::make(),
        ];
    }
}

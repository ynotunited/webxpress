<?php

namespace App\Filament\Resources\Site\SitePageResource\Pages;

use App\Filament\Resources\Site\SitePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSitePages extends ListRecords
{
    protected static string $resource = SitePageResource::class;

    /**
     * @return array<CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New page')
                ->modalHeading('Create a page')
                // The two-tier contract, stated at the moment it is relevant:
                // the form sets the page up, the blocks fill it in.
                ->modalDescription('Set the page up first, then add blocks to it from the page itself. A page with no blocks of its own renders its hero and body.')
                ->modalWidth('lg'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'A page becomes a real page here: set its slug, write its hero, add blocks, then switch it live. Unpublished pages are previewable and return 404 publicly.';
    }
}

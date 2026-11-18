<?php

namespace App\Filament\Resources\Site\SitePageResource\Pages;

use App\Filament\Resources\Site\SitePageResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * A new page is created unpublished.
 *
 * Defaulting to live would mean a half-written page with no blocks is reachable
 * the moment the editor saves the form, which is how half-written pages end up
 * indexed. Publishing is then a separate, deliberate act.
 */
class CreateSitePage extends CreateRecord
{
    protected static string $resource = SitePageResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_published'] = false;

        return $data;
    }

    /**
     * @return array<int, string>
     */
    protected function getRedirectUrl(): string
    {
        // Straight to the page's own screen, because the next step is adding
        // blocks, and the block list lives there.
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

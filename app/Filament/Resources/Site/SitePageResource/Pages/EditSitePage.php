<?php

namespace App\Filament\Resources\Site\SitePageResource\Pages;

use App\Filament\Resources\Site\PageSectionResource;
use App\Filament\Resources\Site\SitePageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Editing a page and editing its copy are two different jobs.
 *
 * A page's own form covers its identity, hero, body, SEO and publish state.
 * The copy that appears inside a designed layout lives in Page Blocks, one
 * record per section.
 *
 * Two actions matter here, and both exist because "I saved the page and nothing
 * on the site changed" is the most common way an editor concludes the CMS is
 * broken:
 *
 *  - Add block, which opens the block form with this page already chosen, so
 *    the copy is one click from the page rather than a second navigation.
 *  - Preview, which renders through the public controller stack — including
 *    while the page is still a draft — so what it shows is what the site will
 *    serve, not a second preview implementation that can drift from the real
 *    templates.
 */
class EditSitePage extends EditRecord
{
    protected static string $resource = SitePageResource::class;

    /**
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addBlock')
                ->label('Add block')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                // page_id pre-fills the block's page, so the editor never has to
                // pick it from a list of every page on the site.
                ->url(fn (): string => PageSectionResource::getUrl('create', [
                    'page_id' => $this->record->getKey(),
                ])),

            Actions\Action::make('blocks')
                ->label('All blocks')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->url(fn (): string => PageSectionResource::getUrl('index', [
                    'tableFilters' => ['page' => ['value' => $this->record->getKey()]],
                ])),

            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('pages.preview', [
                    'slug' => $this->record->slug,
                ]))
                // A draft is the common case, and a new tab beats navigating
                // away from unsaved edits.
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record?->slug)),

            Actions\Action::make('view')
                ->label('View')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => $this->record->url)
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record?->is_published === true
                    && filled($this->record?->slug)),
        ];
    }
}

<?php

namespace App\Filament\Resources\Site\PageSectionResource\Pages;

use App\Filament\Resources\Site\PageSectionResource;
use App\Support\PageSectionSchema;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPageSection extends EditRecord
{
    protected static string $resource = PageSectionResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->viewPageAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * A page record is only publicly reachable once it is published and a
     * route serves its slug, so the link appears only when it would resolve.
     */
    protected function viewPageAction(): Action
    {
        return Action::make('viewPage')
            ->label('View page')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(function (): ?string {
                $page = $this->record->page;

                    if (! $page?->is_published || blank($page->slug)) {
                    return null;
                }

                    return $page->url;
            })
            ->visible(function (): bool {
                $page = $this->record->page;

                    return (bool) $page?->is_published && filled($page?->slug);
            });
    }

    public function getSubheading(): ?string
    {
        $type = $this->record->type;

        $label = PageSectionSchema::isKnown((string) $type)
            ? PageSectionSchema::labels()[$type]
            : 'Unknown type';

        $count = count(PageSectionSchema::controls((string) $type));

        return "{$this->record->key} - {$label} - {$count} editable fields";
    }
}

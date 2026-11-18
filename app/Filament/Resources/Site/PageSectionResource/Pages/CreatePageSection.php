<?php

namespace App\Filament\Resources\Site\PageSectionResource\Pages;

use App\Filament\Resources\Site\PageSectionResource;
use App\Models\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * A block is always created on a page.
 *
 * When the editor arrives from a page's own "Add block" action, the page is
 * already known and is pre-filled — that is the whole point of the action.
 * Without this the query parameter would be ignored and the block would land on
 * whichever page happened to sort first.
 *
 * The parameter is validated against a real page before it is used. It comes
 * from the URL, which the editor controls but a bookmark does not, so an
 * unknown or non-numeric value is ignored rather than written to the form.
 */
class CreatePageSection extends CreateRecord
{
    protected static string $resource = PageSectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $pageId = $this->resolvePageId();

        if ($pageId !== null && blank($data['page_id'] ?? null)) {
            $data['page_id'] = $pageId;
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['label'] ??= Str::headline((string) ($data['key'] ?? 'New section'));

        return $data;
    }

    private function resolvePageId(): ?int
    {
        $raw = request()->query('page_id');

        if (blank($raw) || ! is_numeric($raw)) {
            return null;
        }

        return Page::query()->whereKey($raw)->exists() ? (int) $raw : null;
    }
}

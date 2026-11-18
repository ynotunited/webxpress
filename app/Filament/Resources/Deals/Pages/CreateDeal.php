<?php

namespace App\Filament\Resources\Deals\Pages;

use App\Filament\Resources\Deals\DealResource;
use App\Models\ProjectInquiry;
use Filament\Resources\Pages\CreateRecord;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;

    public function mount(): void
    {
        parent::mount();

        $inquiryId = $this->getRequest()->query('inquiry_id');

        if ($inquiryId === null) {
            return;
        }

        $inquiry = ProjectInquiry::find($inquiryId);

        if ($inquiry === null) {
            return;
        }

        $this->form->fill(array_merge($this->form->getRawState(), [
            'project_inquiry_id' => $inquiry->id,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'project_type' => $inquiry->project_type,
            'scope' => $inquiry->message,
        ]));
    }

    protected function afterCreate(): void
    {
        if ($this->record->inquiry !== null) {
            $this->record->inquiry->markContacted();
        }
    }
}

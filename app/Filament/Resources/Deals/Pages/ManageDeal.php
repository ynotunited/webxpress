<?php

namespace App\Filament\Resources\Deals\Pages;

use App\Filament\Resources\Deals\DealResource;
use App\Mail\DealPaymentLink;
use App\Models\Deal;
use App\Models\Payment;
use App\Services\DealWorkflowService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Mail;

class ManageDeal extends Page
{
    protected static string $resource = DealResource::class;

    protected string $view = 'filament.resources.deals.manage';

    public Deal $record;

    public ?string $generatedLink = null;

    public ?string $generatedLinkKind = null;

    public function mount(Deal $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return $this->record->project_title ?: ($this->record->name ?: 'Deal');
    }

    public function getBreadcrumbs(): array
    {
        return [
            self::getResource()::getUrl() => self::getResource()::getNavigationLabel(),
            'Deal',
        ];
    }

    public function payments(): array
    {
        return $this->record->payments()
            ->with('receipt')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function documents(): array
    {
        return $this->record->documents()
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function sendPortalInvite(): void
    {
        $this->record->refresh();

        try {
            app(DealWorkflowService::class)->sendPortalInvite($this->record);
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Could not send invite')
                ->body($e->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Portal invite sent')
            ->body('Your client can review documents and pay through their private portal link.')
            ->send();
    }

    public function generateUpfrontLink(): void
    {
        $this->generatePaymentLink(Payment::KIND_UPFRONT);
    }

    public function generateDeliveryLink(): void
    {
        $this->generatePaymentLink(Payment::KIND_DELIVERY);
    }

    protected function generatePaymentLink(string $kind): void
    {
        $this->record->refresh();

        try {
            $payment = app(DealWorkflowService::class)->generatePaymentLink($this->record, $kind);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Payment link failed')
                ->body($e->getMessage())
                ->send();

            return;
        }

        if ($payment->isPaid()) {
            Notification::make()
                ->warning()
                ->title('Already paid')
                ->body('This payment has already been received.')
                ->send();

            return;
        }

        $this->generatedLink = $payment->authorization_url;
        $this->generatedLinkKind = $kind;

        Notification::make()
            ->success()
            ->title($kind === Payment::KIND_UPFRONT ? 'Upfront payment link ready' : 'Delivery payment link ready')
            ->body('Copy the link below, or email it to the client.')
            ->send();
    }

    public function emailPaymentLink(): void
    {
        if (blank($this->generatedLink)) {
            Notification::make()
                ->warning()
                ->title('No link yet')
                ->body('Generate a payment link first.')
                ->send();

            return;
        }

        Mail::to($this->record->email)
            ->send(new DealPaymentLink($this->record, $this->generatedLink, $this->generatedLinkKind));

        Notification::make()
            ->success()
            ->title('Payment link emailed')
            ->body('Sent to '.$this->record->email.'.')
            ->send();
    }

    public function markDelivered(): void
    {
        $this->record->refresh();

        if (! $this->record->upfrontPaid()) {
            Notification::make()
                ->warning()
                ->title('Upfront payment missing')
                ->body('The 70% upfront payment must be received before you can mark delivery.')
                ->send();

            return;
        }

        app(DealWorkflowService::class)->markDelivered($this->record);

        Notification::make()
            ->success()
            ->title('Marked as delivered')
            ->body('Generate the delivery payment link to collect the balance.')
            ->send();
    }

    public function markCompleted(): void
    {
        $this->record->refresh();

        if (! $this->record->deliveryPaid()) {
            Notification::make()
                ->warning()
                ->title('Delivery payment not received')
                ->body('Mark this deal complete only after the balance is paid.')
                ->send();

            return;
        }

        $this->record->update([
            'status' => Deal::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        Notification::make()
            ->success()
            ->title('Deal completed')
            ->send();
    }

    public function cancelDeal(): void
    {
        $this->record->refresh();

        if ($this->record->status === Deal::STATUS_COMPLETED) {
            Notification::make()
                ->warning()
                ->title('Cannot cancel')
                ->body('This deal is already completed.')
                ->send();

            return;
        }

        app(DealWorkflowService::class)->cancel($this->record);

        Notification::make()
            ->warning()
            ->title('Deal cancelled')
            ->send();
    }
}

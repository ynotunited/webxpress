<?php

namespace App\Filament\Resources\ProjectInquiries\Pages;

use App\Filament\Resources\ProjectInquiries\ProjectInquiryResource;
use App\Mail\InquiryReplySent;
use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use App\Services\AiReplyService;
use App\Services\InquiryThreadService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Mail;

class RespondInquiry extends Page
{
    protected static string $resource = ProjectInquiryResource::class;

    protected string $view = 'filament.resources.project-inquiries.respond';

    public ProjectInquiry $record;

    public string $replyBody = '';

    public function mount(ProjectInquiry $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Conversation — '.($this->record->name ?: 'Inquiry #'.$this->record->id);
    }

    public function getBreadcrumbs(): array
    {
        return [
            self::getResource()::getUrl() => self::getResource()::getNavigationLabel(),
            self::getResource()::getUrl('view', ['record' => $this->record]) => 'View',
            'Respond',
        ];
    }

    public function thread(): array
    {
        return $this->record->messages
            ->sortBy('received_at')
            ->values()
            ->toArray();
    }

    public function suggestReply(): void
    {
        $service = app(AiReplyService::class);

        if (! $service->enabled()) {
            Notification::make()
                ->warning()
                ->title('AI not configured')
                ->body('Add an AI_API_KEY to your .env to enable AI-suggested replies.')
                ->send();

            return;
        }

        $context = $this->record->messages
            ->sortBy('received_at')
            ->map(fn (InquiryMessage $m) => [
                'direction' => $m->direction,
                'body' => $m->body,
            ])
            ->values()
            ->all();

        $draft = $service->draft($this->record, $context);

        if ($draft === null) {
            Notification::make()
                ->danger()
                ->title('Could not generate a draft')
                ->body('Check the AI provider configuration and try again.')
                ->send();

            return;
        }

        $this->replyBody = $draft;

        Notification::make()
            ->success()
            ->title('Draft ready')
            ->body('Review it before sending — AI suggestions are never sent automatically.')
            ->send();
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
        ]);

        $thread = app(InquiryThreadService::class);
        $messageId = $thread->generateMessageId($this->record);

        $thread->storeOutbound(
            $this->record,
            'reply',
            $this->replySubject(),
            $this->replyBody,
            $messageId,
        );

        $confirmation = new InquiryReplySent($this->record, $this->replyBody, $messageId);

        Mail::to($this->record->email)->send($confirmation);

        $this->record->markContacted();
        $this->record->refresh();
        $this->reset('replyBody');

        Notification::make()
            ->success()
            ->title('Reply sent')
            ->body("Delivered to {$this->record->email}.")
            ->send();
    }

    protected function replySubject(): string
    {
        $first = $this->record->messages->first();

        $base = $first?->subject ?? "Project inquiry #{$this->record->id}";

        return preg_match('/^Re:/i', (string) $base) ? $base : 'Re: '.$base;
    }
}

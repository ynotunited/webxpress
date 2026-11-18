<?php

namespace App\Jobs;

use App\Mail\AiFollowUp;
use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use App\Services\AiReplyService;
use App\Services\InquiryThreadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAiFollowUp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;

    public int $tries = 2;

    public function __construct(
        public ProjectInquiry $inquiry,
    ) {}

    public function handle(): void
    {
        if (! config('mail.ai_follow_up', true)) {
            return;
        }

        $service = app(AiReplyService::class);

        if (! $service->enabled()) {
            Log::info("AI follow-up skipped for inquiry #{$this->inquiry->id} — AI not configured");

            return;
        }

        $draft = $service->draft($this->inquiry, $this->threadContext());

        if ($draft === null) {
            Log::info("AI follow-up skipped for inquiry #{$this->inquiry->id} — provider returned no draft");

            return;
        }

        $threadService = app(InquiryThreadService::class);
        $messageId = $threadService->generateMessageId($this->inquiry);

        $threadService->storeOutbound(
            $this->inquiry,
            'follow_up',
            'Your project brief — a quick note from the Webxpress team',
            $draft,
            $messageId,
        );

        Mail::to($this->inquiry->email)
            ->send(new AiFollowUp($this->inquiry, $draft, $messageId));
    }

    /**
     * @return array<int, array{direction: string, body: string}>
     */
    protected function threadContext(): array
    {
        return $this->inquiry->messages()
            ->orderBy('received_at')
            ->get()
            ->map(fn (InquiryMessage $message) => [
                'direction' => $message->direction,
                'body' => $message->body,
            ])
            ->values()
            ->all();
    }
}

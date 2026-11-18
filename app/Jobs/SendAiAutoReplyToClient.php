<?php

namespace App\Jobs;

use App\Mail\AiAutoReply;
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

class SendAiAutoReplyToClient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;

    public int $tries = 2;

    public function __construct(
        public ProjectInquiry $inquiry,
    ) {}

    public function handle(): void
    {
        if (! config('mail.ai_auto_reply', true)) {
            return;
        }

        $service = app(AiReplyService::class);

        if (! $service->enabled()) {
            Log::info("AI auto-reply skipped for inquiry #{$this->inquiry->id} — AI not configured");

            return;
        }

        if ($this->shouldSkip()) {
            Log::info("AI auto-reply skipped for inquiry #{$this->inquiry->id} — turnover guard active");

            return;
        }

        $draft = $service->draft($this->inquiry, $this->threadContext());

        if ($draft === null) {
            Log::info("AI auto-reply skipped for inquiry #{$this->inquiry->id} — provider returned no draft");

            return;
        }

        $threadService = app(InquiryThreadService::class);
        $messageId = $threadService->generateMessageId($this->inquiry);
        $subject = $this->replySubject();

        $threadService->storeOutbound(
            $this->inquiry,
            'auto_reply',
            $subject,
            $draft,
            $messageId,
        );

        Mail::to($this->inquiry->email)
            ->send(new AiAutoReply($this->inquiry, $subject, $draft, $messageId));
    }

    protected function replySubject(): string
    {
        $first = $this->inquiry->messages()->orderBy('received_at')->first();

        $base = $first?->subject ?? "Project inquiry #{$this->inquiry->id}";

        return preg_match('/^Re:/i', (string) $base) ? $base : 'Re: '.$base;
    }

    protected function shouldSkip(): bool
    {
        $messages = $this->inquiry->messages()
            ->orderBy('received_at')
            ->get();

        $autoReplies = $messages->where('type', 'auto_reply')->count();

        if ($autoReplies >= (int) config('mail.ai_auto_reply_max', 2)) {
            return true;
        }

        $latestOutbound = $messages
            ->where('direction', InquiryMessage::DIR_OUTBOUND)
            ->last();

        return $latestOutbound !== null && $latestOutbound->type === 'reply';
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

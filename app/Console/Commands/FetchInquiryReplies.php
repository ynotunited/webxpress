<?php

namespace App\Console\Commands;

use App\Jobs\SendAiAutoReplyToClient;
use App\Mail\InquiryReply;
use App\Mail\InquiryReplyAcknowledgement;
use App\Models\InquiryMessage;
use App\Services\InquiryThreadService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webklex\IMAP\Facades\Client as ImapClient;

class FetchInquiryReplies extends Command
{
    protected $signature = 'inquiries:fetch-replies
        {--simulate : Parse the configured inbox but store nothing, log what would match}';

    protected $description = 'Poll the shared inbox for new client replies and thread them to inquiries';

    public function handle(InquiryThreadService $thread): int
    {
        $host = config('imap.accounts.default.host', env('IMAP_HOST'));
        $username = config('imap.accounts.default.username', env('IMAP_USERNAME'));

        if (blank($host) || blank($username)) {
            $this->warn('IMAP not configured yet (IMAP_HOST / IMAP_USERNAME / IMAP_PASSWORD). Skipping.');

            return self::SUCCESS;
        }

        try {
            $client = ImapClient::account();
            $client->connect();
            $folder = $client->getFolder('INBOX');

            $messages = $folder->query()
                ->whereUnseen()
                ->leaveUnread()
                ->setFetchBody(true)
                ->get();

            if ($messages->isEmpty()) {
                $this->info('No new messages.');

                return self::SUCCESS;
            }

            $matched = 0;
            $unknown = 0;

            foreach ($messages as $message) {
                try {
                    $result = $this->processMessage($message, $thread);

                    if ($result === true) {
                        $matched++;
                        $message->setFlag('Seen');
                    } else {
                        $unknown++;
                        $this->line("Ignored unmatched message: {$message->get('subject')}");
                    }
                } catch (\Throwable $e) {
                    Log::warning('IMAP message processing failed', ['error' => $e->getMessage()]);
                }
            }

            $client->disconnect();

            $this->info(sprintf('Processed %d new messages: %d matched, %d unmatched.', count($messages), $matched, $unknown));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('IMAP fetch failed', ['error' => $e->getMessage()]);
            $this->error('IMAP fetch failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return bool true when the message was matched to an inquiry
     */
    protected function processMessage(mixed $message, InquiryThreadService $thread): bool
    {
        $subject = $this->attr($message, 'subject');
        $sender = $this->firstAddress($message, 'from');
        $messageId = $this->attr($message, 'message_id');
        $inReplyTo = $this->attr($message, 'in_reply_to');
        $references = array_filter(explode(' ', (string) $this->attr($message, 'references')));

        $inquiry = $thread->resolveInquiry($inReplyTo, $references, $subject, $sender);

        if (! $inquiry) {
            return false;
        }

        $body = $this->body($message);
        $receivedAt = $this->receivedAt($message);

        if ($this->option('simulate')) {
            $this->info("SIMULATE: would thread reply from {$sender} to inquiry #{$inquiry->id}");

            return true;
        }

        $reply = InquiryMessage::create([
            'project_inquiry_id' => $inquiry->id,
            'direction' => InquiryMessage::DIR_INBOUND,
            'type' => 'reply',
            'subject' => $subject,
            'body' => $body,
            'from_email' => $sender,
            'from_name' => $this->firstAddressName($message, 'from'),
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'received_at' => $receivedAt instanceof \DateTimeInterface ? $receivedAt->toDateTime() : now(),
        ]);

        $inquiry->markContacted();

        // Notify the team
        $teamMessageId = $thread->generateMessageId($inquiry);
        Mail::to(config('mail.inquiries_to'))
            ->send(new InquiryReply($inquiry, $reply, $teamMessageId));

        $thread->storeOutbound(
            $inquiry,
            'note',
            'Team notified of reply',
            'Notification sent to '.config('mail.inquiries_to'),
            $teamMessageId,
        );

        // Smart auto-reply to the client (AI-drafted, queued). Falls back to
        // the generic acknowledgement when disabled.
        if (config('mail.ai_auto_reply', true)) {
            SendAiAutoReplyToClient::dispatch($inquiry);
        } elseif (config('mail.inquiries_auto_ack', true)) {
            Mail::to($inquiry->email)
                ->send(new InquiryReplyAcknowledgement($inquiry, $reply));
        }

        $this->info("Threaded reply from {$sender} to inquiry #{$inquiry->id}");

        return true;
    }

    protected function attr(mixed $message, string $key): ?string
    {
        try {
            $value = $message->get($key);
            if ($value === null) {
                return null;
            }

            return method_exists($value, 'first') ? (string) $value->first() : (string) $value;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function receivedAt(mixed $message): CarbonImmutable
    {
        try {
            $value = $message->get('date');

            if ($value instanceof CarbonInterface) {
                return CarbonImmutable::instance($value);
            }

            if (method_exists($value, 'first')) {
                $date = $value->first();
                if ($date instanceof CarbonInterface) {
                    return CarbonImmutable::instance($date);
                }
            }
        } catch (\Throwable) {
        }

        return now()->toImmutable();
    }

    protected function firstAddress(mixed $message, string $key): ?string
    {
        try {
            $value = $message->get($key);
            $addresses = method_exists($value, 'first') ? $value->first() : $value;

            return $addresses?->mail ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function firstAddressName(mixed $message, string $key): ?string
    {
        try {
            $value = $message->get($key);
            $address = method_exists($value, 'first') ? $value->first() : $value;

            return $address?->personal ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function body(mixed $message): string
    {
        try {
            $text = $message->getTextBody();
            if (filled(trim((string) $text))) {
                return trim((string) $text);
            }
        } catch (\Throwable) {
        }

        try {
            $html = $message->getHTMLBody();
            if (filled(trim((string) $html))) {
                $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

                return Str::limit($plain, 4000);
            }
        } catch (\Throwable) {
        }

        return '(empty)';
    }
}

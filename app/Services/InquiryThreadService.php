<?php

namespace App\Services;

use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InquiryThreadService
{
    public function generateMessageId(ProjectInquiry $inquiry): string
    {
        return sprintf('wx-%s-%s@webxpresstech.com.ng', $inquiry->thread_token, Str::random(12));
    }

    public function storeOutbound(
        ProjectInquiry $inquiry,
        string $type,
        string $subject,
        string $body,
        string $messageId,
    ): InquiryMessage {
        return InquiryMessage::create([
            'project_inquiry_id' => $inquiry->id,
            'direction' => InquiryMessage::DIR_OUTBOUND,
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'message_id' => $messageId,
            'received_at' => now(),
        ]);
    }

    /**
     * Try to resolve an inquiry from an inbound email's In-Reply-To /
     * References / subject-thread-token. Returns null when unknown.
     *
     * @param  array<string>  $references
     */
    public function resolveInquiry(string $inReplyTo, array $references, ?string $subject = null, ?string $sender = null): ?ProjectInquiry
    {
        // 1. Match any stored outbound/inbound message-id ancestor
        $ids = array_values(array_filter(array_merge([$inReplyTo], $references)));

        if ($ids !== []) {
            $msg = InquiryMessage::whereIn('message_id', $ids)
                ->orderBy('received_at', 'desc')
                ->first();
            if ($msg) {
                return $msg->inquiry;
            }
        }

        // 2. Fallback: our thread token in the subject, e.g. [WX-aB3xY9zQ].
        //
        // The token is generated with Str::random(8), which is drawn from
        // [A-Za-z0-9]. This pattern used to accept [a-f0-9] instead, so it only
        // ever matched a token that happened to be eight lowercase hex
        // characters — roughly one in 370,000. In practice this branch never
        // fired, every reply fell through to the sender guess below, and that is
        // what made mis-filing common rather than rare.
        if ($subject && preg_match('/\[WX-([A-Za-z0-9]{8})\]/i', $subject, $m)) {
            $inquiry = ProjectInquiry::where('thread_token', $m[1])->first();
            if ($inquiry) {
                return $inquiry;
            }
        }

        // 3. Fallback: same email address — but only when it is unambiguous.
        //
        // This used to take the most recent inquiry from the sender and attach
        // the reply to it, on the assumption that the fallback would rarely be
        // reached. It is reached constantly: a client who replies months later,
        // or from a phone whose mail client stripped the identifiers, lands
        // here. And "most recent" is the wrong thread whenever one person has
        // more than one inquiry — the same client asking about a website in
        // January and an app in June has their January reply filed under June.
        // Two colleagues sharing an info@ address cross-file each other.
        //
        // That is not a cosmetic wart. The mis-filed message is then emailed
        // to the team as a client reply and answered, so one client's project
        // detail can be read back to another. Refusing to guess is the only
        // safe answer; an unmatched message stays unread in the inbox for a
        // human rather than landing on the wrong record.
        if ($sender) {
            $candidates = ProjectInquiry::where('email', $sender)
                ->latest('created_at')
                ->limit(2)
                ->get();

            if ($candidates->count() === 1) {
                return $candidates->first();
            }

            if ($candidates->count() > 1) {
                Log::warning('Ambiguous inquiry reply: sender has several inquiries and the email carried no thread identifier, so it was not filed', [
                    'sender' => $sender,
                    'candidate_inquiry_ids' => $candidates->pluck('id')->all(),
                ]);
            }
        }

        return null;
    }
}

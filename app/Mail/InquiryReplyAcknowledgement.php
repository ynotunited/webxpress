<?php

namespace App\Mail;

use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class InquiryReplyAcknowledgement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public InquiryMessage $reply,
    ) {
        $this->withSymfonyMessage(function (Email $message): void {
            $message->getHeaders()->addIdHeader('In-Reply-To', $this->reply->message_id);
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thanks for your reply',
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->inquiry->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.acknowledgement',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

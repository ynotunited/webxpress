<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class InquiryReplySent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public string $replyBody,
        public string $messageId,
    ) {
        $this->withSymfonyMessage(function (Email $message): void {
            $message->getHeaders()->addIdHeader('Message-ID', $this->messageId);
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reply from Webxpress — '.($this->inquiry->name ?: 'your project'),
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->inquiry->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reply-sent',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

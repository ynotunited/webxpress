<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class AiFollowUp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public string $replyBody,
        public string $messageId,
    ) {
        $this->withSymfonyMessage(function (Email $message): void {
            $message->getHeaders()->addIdHeader('Message-ID', $this->messageId);
            $message->getHeaders()->addTextHeader('X-Thread-Token', $this->inquiry->thread_token);
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your project brief — a quick note from the Webxpress team',
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->inquiry->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai-reply',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

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

class InquiryReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public InquiryMessage $reply,
        public string $messageId,
    ) {
        $this->withSymfonyMessage(function (Email $message): void {
            $message->getHeaders()->addIdHeader('Message-ID', $this->messageId);
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Client replied — '.($this->inquiry->name ?: 'Inquiry #'.$this->inquiry->id),
            from: new Address(config('mail.from.address')),
            to: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealOnboarding extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome aboard — '.($this->deal->project_title ?: 'your project').' is underway',
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->deal->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-onboarding',
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

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

class DealPortalInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your project documents are ready — review and get started',
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->deal->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-portal-invite',
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

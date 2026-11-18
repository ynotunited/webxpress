<?php

namespace App\Mail;

use App\Models\Deal;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealPaymentLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
        public string $paymentUrl,
        public ?string $kind = null,
    ) {}

    public function envelope(): Envelope
    {
        $kindLabel = $this->kind === Payment::KIND_UPFRONT ? 'Upfront payment' : 'Payment';

        return new Envelope(
            subject: $kindLabel.' for '.($this->deal->project_title ?: 'your project').' — Webxpress',
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->deal->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deal-payment-link',
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

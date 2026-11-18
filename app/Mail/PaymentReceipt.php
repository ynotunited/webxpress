<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Receipt $receipt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment receipt '.$this->receipt->receipt_no.' — '.($this->payment->deal->project_title ?: 'your project'),
            from: new Address(config('mail.from.address')),
            replyTo: [new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng'))],
            to: [new Address($this->payment->deal->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
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

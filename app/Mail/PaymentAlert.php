<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the business that money moved, or that it should have but did not.
 *
 * The client already gets a receipt. This goes to the business, because the
 * gap that matters is not "did the client know" but "did we know" — a payment
 * that arrives and is quietly not applied is invisible from the client side
 * until they chase it, and by then it is a dispute rather than a receipt.
 */
class PaymentAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?Payment $payment,
        public ?WebhookEvent $webhookEvent,
        public ?string $reason,
    ) {}

    /**
     * A payment that arrived and was applied.
     */
    public static function received(Payment $payment): self
    {
        return new self($payment, null, null);
    }

    /**
     * A charge that arrived but was not applied, with the reason it was refused.
     */
    public static function needsReview(WebhookEvent $webhookEvent, string $reason): self
    {
        return new self(null, $webhookEvent, $reason);
    }

    public function envelope(): Envelope
    {
        if ($this->payment !== null) {
            $deal = $this->payment->deal;

            return new Envelope(
                subject: sprintf(
                    'Payment received — %s %s (deal #%d)',
                    $this->payment->kind === Payment::KIND_UPFRONT ? 'upfront' : 'delivery',
                    number_format((float) $this->payment->amount, 2),
                    $this->payment->deal_id,
                ),
                replyTo: array_filter([config('mail.inquiries_to')]),
            );
        }

        return new Envelope(
            subject: 'Payment needs review — '.($this->webhookEvent?->reference ?? 'unknown reference'),
            replyTo: array_filter([config('mail.inquiries_to')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-alert',
            with: [
                'payment' => $this->payment,
                'deal' => $this->payment?->deal,
                'webhookEvent' => $this->webhookEvent,
                'reason' => $this->reason,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
<?php

namespace App\Jobs;

use App\Mail\PaymentAlert;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the business that money arrived.
 *
 * A job rather than an inline send for the same reason as SendPaymentReceipt:
 * payment confirmation happens on a webhook retry and on the reconciliation
 * cron as well as in a browser redirect, and none of those should sit waiting on
 * an SMTP timeout. The payment's state is already committed by the time this
 * runs, so a slow mail server cannot delay or roll anything back.
 */
class SendPaymentAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Payment $payment,
    ) {}

    public function handle(): void
    {
        // Only ever announce money that is actually banked. A payment that was
        // rolled back, or that a later reconciliation reversed, must not produce
        // a "money in" email.
        if (! $this->payment->isPaid()) {
            return;
        }

        $to = config('paystack.webhook.alert_email');

        if (blank($to)) {
            return;
        }

        Mail::to($to)->send(PaymentAlert::received($this->payment));
    }
}
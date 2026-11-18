<?php

namespace App\Jobs;

use App\Mail\PaymentReceipt;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Payment $payment,
    ) {}

    public function handle(): void
    {
        if (! $this->payment->isPaid()) {
            return;
        }

        $receipt = $this->payment->receipt;

        if ($receipt === null) {
            return;
        }

        Mail::to($this->payment->deal->email)
            ->send(new PaymentReceipt($this->payment, $receipt));
    }
}

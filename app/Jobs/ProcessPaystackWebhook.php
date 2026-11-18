<?php

namespace App\Jobs;

use App\Mail\PaymentAlert;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\DealWorkflowService;
use App\Services\PaystackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Acts on a webhook event that has already been authenticated and recorded.
 *
 * Running out of line is what makes the endpoint reliable: Paystack allows 30
 * seconds per attempt and gives up after a stretch of failures, and by the time
 * this job has resolved the payment, dispatched the receipt and started
 * onboarding, well under that has passed. The webhook request was answered as
 * soon as the event was on disk.
 *
 * It also runs more than once for the same event, because Paystack retries and
 * the reconciliation cron can reach the same payment by a different route. So
 * every step here assumes it may be the second arrival and has to be a no-op.
 */
class ProcessPaystackWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Three attempts with a widening gap. Retrying a payment webhook is worth
     * it — the alternative is money taken and not recorded — but it must not
     * become a tight loop against a fault that will not clear.
     */
    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /**
     * Passed by id, not as a model.
     *
     * Serialising the model would re-fetch it from the database when the job is
     * handled, which is how a stale in-memory copy of the payload could be
     * written back over a newer one. The id cannot drift from what was
     * recorded.
     */
    public function __construct(
        public int $webhookEventId,
    ) {}

    public function handle(
        DealWorkflowService $workflow,
        PaystackService $paystack,
    ): void {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);

        if ($webhookEvent === null) {
            // The ledger row is append-only and never deleted, so this means the
            // row never committed. Nothing to do and nothing to retry.
            Log::error('Queued webhook event could not be loaded', [
                'webhook_event_id' => $this->webhookEventId,
            ]);

            return;
        }

        if ($webhookEvent->status === WebhookEvent::STATUS_PROCESSED) {
            return;
        }

        $payload = json_decode($webhookEvent->payload, true);

        if (! is_array($payload)) {
            $webhookEvent->markFailed('Stored payload could not be decoded.');

            return;
        }

        $reference = $paystack->eventReference($payload);

        if ($reference === null) {
            $this->flagForReview(
                $webhookEvent,
                'Event carried no transaction reference.',
                $payload
            );

            return;
        }

        $payment = Payment::where('paystack_reference', $reference)->first();

        /*
         * A charge we cannot attribute to an invoice. The money is real and is
         * sitting in the Paystack balance, so this is a person to phone rather
         * than a warning to swallow. Recorded and alerted, never guessed at.
         */
        if ($payment === null) {
            $this->flagForReview(
                $webhookEvent,
                'Charge succeeded for reference '.$reference.' which matches no payment on this site.',
                $payload
            );

            return;
        }

        // Record what the gateway asserted before interpreting it, so a dispute
        // can be settled against the gateway's own numbers.
        $payment->forceFill([
            'gateway_amount' => $paystack->eventAmountKobo($payload),
            'gateway_currency' => $paystack->eventCurrency($payload),
            'gateway_status' => $paystack->eventStatus($payload),
            'gateway_charged_at' => $paystack->eventChargedAt($payload),
            'gateway_authorization_code' => $paystack->eventAuthorizationCode($payload),
        ])->save();

        /*
         * The amount is the check that matters.
         *
         * The signature proves Paystack sent this. It does not prove the amount
         * is the one we asked for, and nothing else in the path compares them. A
         * site that marks a five-million-naira invoice paid because a signed
         * event for a smaller charge arrived is a hole worth exactly that
         * difference, so the two are compared in integer kobo — never floats,
         * where rounding decides whether a payment is accepted.
         */
        $expectedKobo = (int) round(((float) $payment->amount) * 100);
        $chargedKobo = $paystack->eventAmountKobo($payload);

        if ($chargedKobo === null || $chargedKobo !== $expectedKobo) {
            $payment->forceFill([
                'failure_reason' => 'Amount mismatch: expected '.$expectedKobo.' kobo, gateway reported '.($chargedKobo ?? 'none').'.',
            ])->save();

            $this->flagForReview(
                $webhookEvent,
                'Amount mismatch on '.$reference.': invoice is '.$payment->amount.' NGN, gateway charged '.($chargedKobo === null ? 'nothing' : $chargedKobo / 100).'.',
                $payload
            );

            return;
        }

        $currency = $paystack->eventCurrency($payload);
        $expectedCurrency = (string) config('paystack.webhook.currency', 'NGN');

        if ($currency !== null && $currency !== $expectedCurrency) {
            $payment->forceFill([
                'failure_reason' => 'Currency mismatch: expected '.$expectedCurrency.', gateway reported '.$currency.'.',
            ])->save();

            $this->flagForReview(
                $webhookEvent,
                'Currency mismatch on '.$reference.': expected '.$expectedCurrency.', gateway reported '.$currency.'.',
                $payload
            );

            return;
        }

        $gatewayStatus = $paystack->eventStatus($payload);

        if ($gatewayStatus !== null && $gatewayStatus !== 'success') {
            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => 'Gateway reported status "'.$gatewayStatus.'".',
            ])->save();

            $webhookEvent->markFailed('Gateway status was "'.$gatewayStatus.'".');

            return;
        }

        $workflow->markPaymentPaid($payment, $paystack->eventChargedAt($payload), 'webhook');

        $webhookEvent->markProcessed();
    }

    /**
     * Something is wrong that a human has to resolve, and it involves money.
     *
     * Fails the job if the alert cannot be sent, because an unrecorded
     * discrepancy is how a payment problem becomes a reconciliation problem
     * three weeks later. The webhook row is marked first so the outcome is not
     * lost even if the alert fails.
     */
    protected function flagForReview(WebhookEvent $webhookEvent, string $reason, array $payload): void
    {
        $webhookEvent->markFailed($reason);

        Log::error('Paystack webhook needs manual review', [
            'webhook_event_id' => $webhookEvent->id,
            'reference' => $webhookEvent->reference,
            'reason' => $reason,
        ]);

        $this->alertAdmin($webhookEvent, $reason);
    }

    protected function alertAdmin(WebhookEvent $webhookEvent, string $reason): void
    {
        if (! config('paystack.webhook.notify_on_payment', true)) {
            return;
        }

        $to = config('paystack.webhook.alert_email');

        if (blank($to)) {
            Log::warning('Payment alert could not be sent — no alert address configured', [
                'webhook_event_id' => $webhookEvent->id,
            ]);

            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($to)->send(
                PaymentAlert::needsReview($webhookEvent, $reason)
            );
        } catch (Throwable $e) {
            // Rethrown so the queue retries: the discrepancy is already durably
            // marked on the webhook row, so nothing is lost if every attempt
            // fails, but a human still needs to be told.
            throw $e;
        }
    }
}
<?php

namespace App\Services;

use App\Jobs\ProcessPaystackWebhook;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The gate every inbound payment event passes before the site acts on it.
 *
 * The order is deliberate and is the whole point: confirm the request is even
 * addressed to us, prove it came from Paystack, record it immutably, prove we
 * have not already acted on it, and only then queue the work. Nothing that
 * changes a payment runs before the first four steps have succeeded.
 *
 * Every event is recorded, including the ones refused. A rejected signature is
 * exactly the thing that should be visible when it happens in a pattern, and a
 * burst of them is the signature of someone probing the endpoint.
 */
class PaystackWebhookService
{
    public function __construct(protected PaystackService $paystack) {}

    /**
     * Handle an inbound webhook. Always answers 200 once the event is safely
     * recorded, and 401 when the request could not be authenticated.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = (string) $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $sourceIp = (string) $request->ip();

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            // Unparseable but possibly genuine — a truncated delivery. Recorded
            // so the attempt is on file, then refused: there is nothing to act
            // on, and acknowledging it would stop Paystack retrying.
            $this->recordRejected($rawBody, null, null, $sourceIp, 'Body was not valid JSON.');

            return response()->json(['status' => 'invalid payload'], 401);
        }

        // Step 2: prove the request came from Paystack. Without this the body is
        // just a stranger's JSON with the power to mark invoices paid.
        if (! $this->paystack->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Paystack webhook rejected — signature did not verify', [
                'source_ip' => $sourceIp,
                'event' => $this->paystack->eventName($payload),
                'reference' => $this->paystack->eventReference($payload),
            ]);

            $this->recordRejected(
                $rawBody,
                $this->paystack->eventName($payload),
                $this->paystack->eventReference($payload),
                $sourceIp,
                'Signature did not verify.'
            );

            // 401, not 200. Returning success for a request we refused tells
            // Paystack the event was handled when it was not, which both hides a
            // real integration fault and hands an attacker a 200 to aim for.
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $event = $this->paystack->eventName($payload);
        $reference = $this->paystack->eventReference($payload);

        // Step 3: only the events we understand move money. An unhandled type is
        // recorded rather than dropped, so "Paystack sent charge.dispute.create
        // and we ignored it" is a question the ledger can answer.
        if ($event === null || ! in_array($event, (array) config('paystack.webhook.handled_events', []), true)) {
            $this->record($rawBody, $event, $reference, $sourceIp, true, WebhookEvent::STATUS_IGNORED, 'Event type is not handled.');

            return response()->json(['status' => 'ignored']);
        }

        // Step 4: write it down before acting on it. The unique dedupe key is
        // what makes redelivery a no-op, and it only works if the row exists
        // before any side effect — so this insert deliberately precedes the
        // dispatch below.
        try {
            $webhookEvent = $this->record(
                $rawBody,
                $event,
                $reference,
                $sourceIp,
                true,
                WebhookEvent::STATUS_RECEIVED,
                null
            );
        } catch (QueryException $e) {
            // The unique key rejected a delivery we have already seen. This is
            // the designed outcome of a retry, not an error: Paystack retries
            // for 72 hours by design and the dashboard can resend any event at
            // any time.
            if ($this->isDuplicateKey($e)) {
                Log::info('Paystack webhook redelivery suppressed', [
                    'event' => $event,
                    'reference' => $reference,
                    'source_ip' => $sourceIp,
                ]);

                return response()->json(['status' => 'already processed']);
            }

            throw $e;
        }

        // Step 5: acknowledge, then act. Paystack times each attempt out at 30
        // seconds and retries failures for 72 hours, so the request is answered
        // as soon as the event is durably recorded and the work is queued.
        ProcessPaystackWebhook::dispatch($webhookEvent->id);

        $webhookEvent->markQueued();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Write the event to the ledger.
     *
     * A duplicate key is allowed to propagate: the caller catches it, because
     * only the caller knows whether this insert was the first delivery.
     */
    protected function record(
        string $rawBody,
        ?string $event,
        ?string $reference,
        string $sourceIp,
        bool $signatureValid,
        string $status,
        ?string $failureReason,
    ): WebhookEvent {
        return WebhookEvent::create([
            'provider' => 'paystack',
            'event' => $event ?? 'unknown',
            'reference' => $reference,
            'dedupe_key' => WebhookEvent::dedupeKey(
                'paystack',
                $event ?? 'unknown',
                $reference,
                $rawBody,
                $signatureValid ? 'accepted' : 'rejected'
            ),
            'payload' => $rawBody,
            'source_ip' => $sourceIp,
            'signature_valid' => $signatureValid,
            'status' => $status,
            'failure_reason' => $failureReason,
            'payload_received_at' => now(),
            'attempts' => $status === WebhookEvent::STATUS_RECEIVED ? 1 : 0,
        ]);
    }

    /**
     * Record a request we refused.
     *
     * Best effort by design, and the difference matters: a duplicate key on the
     * acceptance path means "this is a replay, stop", which is a decision worth
     * making loudly. Here it only means the same forged body has been seen
     * before, which is not new information — so it is absorbed. Letting it
     * escape would hand anyone who has captured one legitimate request the
     * ability to raise a server error with a replay of it.
     */
    protected function recordRejected(
        string $rawBody,
        ?string $event,
        ?string $reference,
        string $sourceIp,
        string $reason,
    ): void {
        try {
            $this->record(
                $rawBody,
                $event,
                $reference,
                $sourceIp,
                false,
                WebhookEvent::STATUS_FAILED,
                $reason
            );
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }
        }
    }

    /**
     * Distinguish "this event is already on file" from a genuine database
     * failure. Without this, a connection problem during an insert would be
     * reported to Paystack as a duplicate and the event would be silently
     * discarded — losing exactly the payment we were trying to record.
     */
    protected function isDuplicateKey(QueryException $e): bool
    {
        // SQLSTATE 23000 is an integrity constraint violation; 23505 is the same
        // in PostgreSQL. Anything else is a real fault.
        if (! in_array((string) $e->getCode(), ['23000', '23505'], true)) {
            return false;
        }

        return str_contains(
            mb_strtolower($e->getMessage()),
            'dedupe_key'
        ) || str_contains(mb_strtolower($e->getMessage()), 'unique');
    }
}
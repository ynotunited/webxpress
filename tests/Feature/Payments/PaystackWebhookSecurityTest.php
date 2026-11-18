<?php

namespace Tests\Feature\Payments;

use App\Jobs\ProcessPaystackWebhook;
use App\Jobs\SendOnboarding;
use App\Jobs\SendPaymentAlert;
use App\Jobs\SendPaymentReceipt;
use App\Mail\PaymentAlert;
use App\Models\Deal;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\WebhookEvent;
use App\Services\DealWorkflowService;
use App\Services\PaystackService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The webhook endpoint as an attacker and as Paystack.
 *
 * Nothing here is about the happy path in the abstract — it is about the three
 * things that decide whether a stranger can move money: whether a request is
 * believed, whether it can be replayed, and whether a believed request is
 * checked against the invoice it claims to settle.
 *
 * The signature is computed the same way Paystack computes it, from the raw
 * request body, because a test that signs a re-encoded array would pass against
 * an implementation that rejects every genuine event.
 */
class PaystackWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'sk_test_abcdef0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => $this->secret,
            // Off by default in the test environment so that the many legitimate
            // events below are not each one mailing somebody. The alerting
            // itself is covered separately.
            'paystack.webhook.notify_on_payment' => false,
        ]);
    }

    private function sign(string $body): array
    {
        return ['x-paystack-signature' => hash_hmac('sha512', $body, $this->secret)];
    }

    private function event(array $overrides = []): string
    {
        $payload = array_replace_recursive([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WX-REFERENCEREFERENCE1',
                'amount' => 700_000_00,
                'currency' => 'NGN',
                'status' => 'success',
                'paid_at' => '2026-10-04T10:00:00.000Z',
                'authorization' => ['authorization_code' => 'AUTH_123'],
            ],
        ], $overrides);

        return json_encode($payload);
    }

    /**
     * Post a raw body to the webhook as the gateway would.
     *
     * Header names have to be translated into the CGI form Symfony expects
     * (`HTTP_X_PAYSTACK_SIGNATURE`). Passing them as-is builds a server array
     * with a key nothing reads, so the request arrives with no signature header
     * at all — which looks exactly like a rejected forgery and hides the real
     * behaviour under test.
     */
    private function postWebhook(string $body, array $headers = [], string $ip = '52.31.139.75')
    {
        $server = [
            'REMOTE_ADDR' => $ip,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        foreach ($headers as $name => $value) {
            $name = strtoupper(str_replace('-', '_', $name));

            // Content-Type and Content-Length are the two headers that do not
            // carry the HTTP_ prefix.
            $server[in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $name : 'HTTP_'.$name] = $value;
        }

        return $this->call('POST', '/webhooks/paystack', [], [], [], $server, $body);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 1 — signature verification on every event
    |--------------------------------------------------------------------------
    */

    public function test_a_signed_charge_is_accepted_and_queued(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);

        $response = $this->postWebhook($body, $this->sign($body));

        $response->assertOk()->assertJson(['status' => 'ok']);

        // The payment must not be marked paid synchronously: acknowledging and
        // then working is what keeps this inside Paystack's 30 second budget.
        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);

        Bus::assertDispatched(ProcessPaystackWebhook::class);
    }

    public function test_an_unsigned_request_is_refused_with_401(): void
    {
        Bus::fake();

        $this->postWebhook($this->event())
            ->assertStatus(401)
            ->assertJson(['status' => 'invalid signature']);

        // Nothing was queued: a request we cannot authenticate must not reach
        // the payment path at all.
        Bus::assertNothingDispatched();
    }

    public function test_a_request_signed_with_the_wrong_key_is_refused(): void
    {
        $body = $this->event();

        $this->postWebhook($body, ['x-paystack-signature' => hash_hmac('sha512', $body, 'sk_test_wrong')])
            ->assertStatus(401);
    }

    public function test_a_signature_over_different_bytes_is_refused(): void
    {
        // The attack this stops: take a valid signature and attach it to a
        // tampered body. Only possible if verification runs over anything other
        // than the exact bytes received.
        $signed = $this->event(['data' => ['amount' => 100]]);
        $tampered = $this->event(['data' => ['amount' => 700_000_00]]);

        $this->assertNotSame($signed, $tampered);

        $this->postWebhook($tampered, $this->sign($signed))->assertStatus(401);
    }

    public function test_a_refused_signature_is_still_recorded_for_audit(): void
    {
        $this->postWebhook($this->event(), ['x-paystack-signature' => 'nonsense']);

        $event = WebhookEvent::first();

        $this->assertNotNull($event, 'A rejected request must leave a trace.');
        $this->assertFalse($event->signature_valid);
        $this->assertSame(WebhookEvent::STATUS_FAILED, $event->status);
        $this->assertSame('Signature did not verify.', $event->failure_reason);
    }

    public function test_the_endpoint_refuses_when_the_gateway_is_unconfigured(): void
    {
        // Without a secret key nothing can be verified, so nothing can be
        // believed. Failing open here would mean an unconfigured server accepts
        // unsigned payment events.
        config(['services.paystack.secret_key' => '']);

        $this->postWebhook($this->event(), $this->sign($this->event()))
            ->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 2 — idempotency and replay protection
    |--------------------------------------------------------------------------
    */

    public function test_the_same_event_delivered_twice_is_applied_once(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);
        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);

        $this->postWebhook($body, $this->sign($body))->assertOk();
        $this->postWebhook($body, $this->sign($body))->assertOk()->assertJson(['status' => 'already processed']);

        // One row for one event, and one dispatch — not two.
        $this->assertSame(1, WebhookEvent::count());
        Bus::assertDispatchedTimes(ProcessPaystackWebhook::class, 1);
    }

    public function test_a_replay_is_refused_even_though_its_signature_is_valid(): void
    {
        $payment = Payment::factory()->create(['amount' => 700_000]);
        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);

        $this->postWebhook($body, $this->sign($body))->assertOk();

        // Replayed verbatim, signature and all — exactly what an attacker who
        // captured a legitimate request would send.
        $replay = $this->postWebhook($body, $this->sign($body));

        $replay->assertOk();
        $this->assertSame(1, WebhookEvent::count());
    }

    public function test_two_different_charges_are_not_mistaken_for_a_replay(): void
    {
        Bus::fake();

        $first = Payment::factory()->create(['amount' => 700_000]);
        $second = Payment::factory()->create(['amount' => 300_000, 'kind' => Payment::KIND_DELIVERY]);

        $a = $this->event(['data' => ['reference' => $first->paystack_reference]]);
        $b = $this->event(['data' => ['reference' => $second->paystack_reference, 'amount' => 300_000_00]]);

        $this->postWebhook($a, $this->sign($a))->assertOk();
        $this->postWebhook($b, $this->sign($b))->assertOk();

        // Suppressing the second would be discarding real money.
        $this->assertSame(2, WebhookEvent::count());
        Bus::assertDispatchedTimes(ProcessPaystackWebhook::class, 2);
    }

    public function test_the_ledger_refuses_to_be_rewritten(): void
    {
        $payment = Payment::factory()->create(['amount' => 700_000]);
        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        $event = WebhookEvent::first();

        // The outcome of processing is allowed to be stamped...
        $event->markProcessed();
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $event->refresh()->status);

        // ...but the evidence of what arrived is not. Assigned and saved, so the
        // model's own guard is what refuses it — a mass update would bypass the
        // event entirely and prove nothing.
        $tampered = WebhookEvent::find($event->id);
        $tampered->payload = '{"event":"forged"}';

        $this->expectException(\LogicException::class);

        $tampered->save();
    }

    public function test_the_ledger_refuses_deletion(): void
    {
        $payment = Payment::factory()->create(['amount' => 700_000]);
        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        $this->expectException(\LogicException::class);

        WebhookEvent::first()->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 3 — source allowlisting
    |--------------------------------------------------------------------------
    */

    public function test_a_request_from_a_paystack_address_is_allowed(): void
    {
        Bus::fake();

        $this->postWebhook($this->event(), $this->sign($this->event()), '52.49.173.169')
            ->assertOk();
    }

    public function test_a_request_from_anywhere_else_is_blocked_before_the_signature_is_checked(): void
    {
        Bus::fake();

        // Correctly signed, but from the wrong address. Both layers apply, and
        // the cheap one runs first.
        $body = $this->event();

        $this->postWebhook($body, $this->sign($body), '203.0.113.9')
            ->assertStatus(403)
            ->assertJson(['status' => 'forbidden source']);

        $this->assertSame(0, WebhookEvent::count(), 'A blocked request must not be recorded as a Paystack event.');
        Bus::assertNothingDispatched();
    }

    public function test_the_allowlist_can_be_relaxed_without_disabling_signature_checks(): void
    {
        // The escape hatch for "Paystack added an address". It must relax only
        // the address check: an unsigned request is still refused.
        config(['paystack.webhook.enforce_ip_allowlist' => false]);

        $body = $this->event();

        $this->postWebhook($body, $this->sign($body), '203.0.113.9')->assertOk();
        $this->postWebhook($body, [], '203.0.113.9')->assertStatus(401);
    }

    public function test_an_empty_allowlist_does_not_silently_disable_filtering(): void
    {
        Log::spy();

        config(['paystack.webhook.allowed_ips' => []]);

        $body = $this->event();

        // Signature still verified; the misconfiguration is made loud.
        $this->postWebhook($body, [], '203.0.113.9')->assertStatus(401);
        Log::shouldHaveReceived('warning');
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 4 — verify, then act
    |--------------------------------------------------------------------------
    */

    public function test_a_charge_for_an_unknown_reference_is_flagged_not_applied(): void
    {
        Bus::fake();

        $body = $this->event(['data' => ['reference' => 'WX-SOMETHINGELSE']]);

        $this->postWebhook($body, $this->sign($body))->assertOk();

        // The refusal happens on the queue, not in the request — that is the
        // point of acknowledging early — so the job is run here explicitly.
        $event = WebhookEvent::first();

        (new ProcessPaystackWebhook($event->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $this->assertSame(WebhookEvent::STATUS_FAILED, $event->refresh()->status);
        $this->assertStringContainsString('matches no payment', $event->refresh()->failure_reason);
    }

    public function test_an_unhandled_event_type_is_acknowledged_and_recorded(): void
    {
        $body = $this->event(['event' => 'charge.dispute.create']);

        $this->postWebhook($body, $this->sign($body))
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        // Recorded so "we ignored a dispute" is answerable, not guessed at.
        $this->assertSame(WebhookEvent::STATUS_IGNORED, WebhookEvent::first()->status);
    }

    public function test_a_charge_for_the_wrong_amount_is_refused(): void
    {
        Bus::fake();

        // The invoice is for 700,000. The gateway says it took 70. The signature
        // is genuine — this is not a forgery test, it is the check that a
        // correctly signed event cannot settle the wrong invoice.
        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => [
            'reference' => $payment->paystack_reference,
            'amount' => 70_000_00,
        ]]);

        $this->postWebhook($body, $this->sign($body))->assertOk();

        $job = new ProcessPaystackWebhook(WebhookEvent::first()->id);
        $job->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertStringContainsString('Amount mismatch', $payment->failure_reason);
    }

    public function test_a_charge_in_the_wrong_currency_is_refused(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => [
            'reference' => $payment->paystack_reference,
            'currency' => 'USD',
        ]]);

        $this->postWebhook($body, $this->sign($body))->assertOk();

        $job = new ProcessPaystackWebhook(WebhookEvent::first()->id);
        $job->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertStringContainsString('Currency mismatch', $payment->failure_reason);
    }

    public function test_a_body_that_is_not_json_is_refused(): void
    {
        $this->postWebhook('{not json', $this->sign('{not json'))
            ->assertStatus(401);

        $this->assertSame(WebhookEvent::STATUS_FAILED, WebhookEvent::first()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 5 — no double charge, no double effect
    |--------------------------------------------------------------------------
    */

    public function test_the_same_payment_confirmed_by_two_routes_receipts_and_emails_once(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        $job = new ProcessPaystackWebhook(WebhookEvent::first()->id);

        // The webhook arriving, then the reconciliation cron reaching the same
        // payment by its own route — the realistic double-confirmation.
        $job->handle(app(DealWorkflowService::class), app(PaystackService::class));
        app(DealWorkflowService::class)
            ->markPaymentPaid($payment->refresh(), null, 'reconcile');

        $this->assertSame(1, Receipt::where('payment_id', $payment->id)->count());
        Bus::assertDispatched(SendPaymentReceipt::class, 1);
    }

    public function test_two_payments_cannot_share_one_gateway_reference(): void
    {
        $reference = Payment::factory()->create()->paystack_reference;

        $this->expectException(QueryException::class);

        Payment::factory()->create(['paystack_reference' => $reference]);
    }

    public function test_a_deal_cannot_have_two_payments_for_the_same_phase(): void
    {
        $deal = Deal::factory()->create();
        Payment::factory()->create(['deal_id' => $deal->id, 'kind' => Payment::KIND_UPFRONT]);

        $this->expectException(QueryException::class);

        Payment::factory()->create(['deal_id' => $deal->id, 'kind' => Payment::KIND_UPFRONT]);
    }

    public function test_generating_a_link_twice_reuses_the_same_gateway_transaction(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test/abc',
                    'access_code' => 'ACCESS1',
                    'reference' => 'WX-STABLE1',
                ],
            ]),
        ]);

        $deal = Deal::factory()->create(['amount' => 1_000_000]);
        $workflow = app(DealWorkflowService::class);

        $first = $workflow->generatePaymentLink($deal, Payment::KIND_UPFRONT);
        $second = $workflow->generatePaymentLink($deal->refresh(), Payment::KIND_UPFRONT);

        // Two live transactions against one invoice is how a client gets charged
        // twice. There must be one.
        $this->assertSame($first->paystack_reference, $second->paystack_reference);
        Http::assertSentCount(1);

        // And still only one payment row for the phase.
        $this->assertSame(1, $deal->payments()->count());
    }

    public function test_a_new_reference_can_still_be_forced_deliberately(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test/abc',
                    'access_code' => 'ACCESS1',
                    'reference' => 'WX-FRESH1',
                ],
            ]),
        ]);

        $deal = Deal::factory()->create(['amount' => 1_000_000]);
        $workflow = app(DealWorkflowService::class);

        $first = $workflow->generatePaymentLink($deal, Payment::KIND_UPFRONT);
        $forced = $workflow->generatePaymentLink($deal->refresh(), Payment::KIND_UPFRONT, forceNewReference: true);

        Http::assertSentCount(2);
        $this->assertSame('WX-FRESH1', $forced->paystack_reference);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 6 — the business moves, not just the row
    |--------------------------------------------------------------------------
    */

    public function test_a_verified_upfront_payment_starts_the_project(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);
        $deal = $payment->deal;

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $this->assertSame(Payment::STATUS_PAID, $payment->refresh()->status);
        $this->assertSame(Deal::STATUS_IN_PROGRESS, $deal->refresh()->status);

        // The gateway's own settlement time, not the time we happened to read it.
        $this->assertSame('2026-10-04', $payment->refresh()->paid_at->toDateString());
        $this->assertSame('webhook', $payment->refresh()->confirmed_via);

        Bus::assertDispatched(SendPaymentReceipt::class);
        Bus::assertDispatched(SendOnboarding::class);
    }

    public function test_a_verified_delivery_payment_completes_the_project(): void
    {
        Bus::fake();

        $payment = Payment::factory()->delivery()->create(['amount' => 300_000]);
        $deal = $payment->deal;

        $body = $this->event([
            'data' => ['reference' => $payment->paystack_reference, 'amount' => 300_000_00],
        ]);
        $this->postWebhook($body, $this->sign($body));

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $this->assertSame(Deal::STATUS_COMPLETED, $deal->refresh()->status);
        Bus::assertDispatched(SendPaymentReceipt::class);
    }

    public function test_money_arriving_for_a_cancelled_deal_is_flagged_rather_than_dropped(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);
        $payment->deal->update(['status' => Deal::STATUS_CANCELLED]);

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        // Not silently marked paid, and not silently forgotten: the charge is on
        // the payment row as something a person has to resolve.
        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
        $this->assertStringContainsString('cancelled deal', $payment->refresh()->failure_reason);
    }

    public function test_the_business_is_emailed_about_a_payment_that_needs_review(): void
    {
        Bus::fake();
        Mail::fake();

        config([
            'paystack.webhook.notify_on_payment' => true,
            'paystack.webhook.alert_email' => 'accounts@webxpresstech.com.ng',
        ]);

        $body = $this->event(['data' => ['reference' => 'WX-UNKNOWNREFERENCE']]);
        $this->postWebhook($body, $this->sign($body));

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        Mail::assertSent(
            PaymentAlert::class,
            fn ($mail) => $mail->hasTo('accounts@webxpresstech.com.ng')
        );
    }

    public function test_the_business_is_emailed_once_when_a_payment_lands(): void
    {
        Bus::fake();

        config([
            'paystack.webhook.notify_on_payment' => true,
            'paystack.webhook.alert_email' => 'accounts@webxpresstech.com.ng',
        ]);

        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        $workflow = app(DealWorkflowService::class);

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle($workflow, app(PaystackService::class));

        // A second confirmation of the same money must not produce a second
        // "money in" email, or the alert channel becomes noise.
        $workflow->markPaymentPaid($payment->refresh(), null, 'reconcile');

        Bus::assertDispatched(SendPaymentAlert::class, 1);
    }

    public function test_the_business_alert_names_the_client_and_the_amount(): void
    {
        Bus::fake();
        Mail::fake();

        config([
            'paystack.webhook.notify_on_payment' => true,
            'paystack.webhook.alert_email' => 'accounts@webxpresstech.com.ng',
        ]);

        $payment = Payment::factory()->create(['amount' => 700_000]);
        $payment->deal->update(['name' => 'Ngozi Adeleke']);

        app(DealWorkflowService::class)->markPaymentPaid($payment->refresh());

        // The job actually runs here, so the mail really is composed — a
        // dispatched-but-broken alert is not an alert.
        (new SendPaymentAlert($payment->refresh()))->handle();

        Mail::assertSent(
            PaymentAlert::class,
            fn ($mail) => $mail->hasTo('accounts@webxpresstech.com.ng')
        );
    }

    /**
     * A regression test for a real defect this suite found.
     *
     * A captured legitimate body replayed *without* a valid signature has
     * byte-identical content to the event already on file. When both were keyed
     * the same, the replay's rejection collided with the accepted row's unique
     * key and surfaced as a 500 — so anyone holding one captured request could
     * raise server errors at will, and a rejected request would look like a
     * server fault instead of a refusal.
     */
    public function test_a_forged_replay_of_an_accepted_event_is_refused_without_a_server_error(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);
        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);

        $this->postWebhook($body, $this->sign($body))->assertOk();

        // Same bytes, no valid signature.
        $this->postWebhook($body, ['x-paystack-signature' => hash_hmac('sha512', $body, 'sk_test_wrong')])
            ->assertStatus(401)
            ->assertJson(['status' => 'invalid signature']);

        // And repeating the forgery is absorbed rather than escalating.
        $this->postWebhook($body, ['x-paystack-signature' => 'forged'])
            ->assertStatus(401);

        // Only the one genuine event queued work, and nothing was marked paid.
        Bus::assertDispatchedTimes(ProcessPaystackWebhook::class, 1);
        $this->assertSame(Payment::STATUS_PENDING, $payment->refresh()->status);
    }

    public function test_the_gateway_assertion_is_kept_alongside_the_invoice(): void
    {
        Bus::fake();

        $payment = Payment::factory()->create(['amount' => 700_000]);

        $body = $this->event(['data' => ['reference' => $payment->paystack_reference]]);
        $this->postWebhook($body, $this->sign($body));

        (new ProcessPaystackWebhook(WebhookEvent::first()->id))
            ->handle(app(DealWorkflowService::class), app(PaystackService::class));

        $payment = $payment->refresh();

        // What we expected next to what the gateway said, so a disagreement is
        // reconstructable months later without trusting either system's memory.
        $this->assertSame(700_000_00, $payment->gateway_amount);
        $this->assertSame('NGN', $payment->gateway_currency);
        $this->assertSame('AUTH_123', $payment->gateway_authorization_code);
    }
}

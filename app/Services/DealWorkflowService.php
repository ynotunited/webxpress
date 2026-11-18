<?php

namespace App\Services;

use App\Jobs\SendOnboarding;
use App\Jobs\SendPaymentAlert;
use App\Jobs\SendPaymentReceipt;
use App\Mail\DealPortalInvite;
use App\Models\Deal;
use App\Models\DealDocument;
use App\Models\Payment;
use App\Models\ProjectInquiry;
use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class DealWorkflowService
{
    public function create(string $name, string $email, float $amount): Deal
    {
        return Deal::create([
            'name' => $name,
            'email' => $email,
            'amount' => $amount,
            'project_inquiry_id' => null,
            'status' => Deal::STATUS_DRAFT,
            'created_via' => 'manual',
            'access_token' => $this->newAccessToken(),
        ]);
    }

    public function newAccessToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (Deal::where('access_token', $token)->exists());

        return $token;
    }

    protected function newReference(): string
    {
        return 'WX-'.strtoupper(Str::random(20));
    }

    public function upfrontAmount(Deal $deal): float
    {
        $amount = (float) $deal->amount;

        return round($amount * ((int) $deal->upfront_percent / 100), 2);
    }

    public function deliveryAmount(Deal $deal): float
    {
        return round((float) $deal->amount - $this->upfrontAmount($deal), 2);
    }

    public function sendPortalInvite(Deal $deal): void
    {
        $deal->update([
            'status' => Deal::STATUS_DOCUMENTS_SENT,
            'documents_sent_at' => now(),
        ]);

        Mail::to($deal->email)
            ->send(new DealPortalInvite($deal));
    }

    /**
     * Create (or refresh) the Paystack payment link for a deal phase.
     *
     * Deliberately not idempotent in the way it looks. Issuing a link means
     * creating a live transaction at the gateway, and the old version did that
     * every time it was called for a pending payment — so pressing "Generate
     * payment link" twice left two real Paystack transactions outstanding
     * against one invoice, and nothing stopped a client settling both. Two
     * guards now close that:
     *
     * An existing link is reused unless the caller explicitly asks for a new
     * one, because most calls are a visitor clicking Pay and a fresh
     * transaction there would be a second way to pay one invoice.
     *
     * And even a deliberate refresh reuses the outstanding reference while that
     * transaction is still live at the gateway, so a second link cannot point at
     * money already owed. Force-replacing it is available for the case where
     * the first link genuinely expired, and is named so that using it is a
     * decision in the diff rather than an accident.
     */
    public function generatePaymentLink(
        Deal $deal,
        string $kind,
        bool $forceNewReference = false,
    ): Payment {
        $amount = $kind === Payment::KIND_UPFRONT
            ? $this->upfrontAmount($deal)
            : $this->deliveryAmount($deal);

        $payment = $deal->payments()->where('kind', $kind)->first();

        if ($payment === null) {
            $payment = $deal->payments()->create([
                'kind' => $kind,
                'amount' => $amount,
                'status' => Payment::STATUS_PENDING,
            ]);
        }

        if ($payment->isPaid()) {
            return $payment;
        }

        if (! $forceNewReference && filled($payment->authorization_url) && filled($payment->paystack_reference)) {
            return $payment;
        }

        $service = app(PaystackService::class);

        if (! $service->enabled()) {
            throw new RuntimeException('Paystack is not configured. Add PAYSTACK_SECRET_KEY to your .env.');
        }

        // The same reference for the same Payment row, on every refresh. Two
        // links pointing at one invoice then resolve to one transaction, and
        // the payments_paystack_reference unique index makes that a database
        // guarantee rather than a convention.
        $reference = $payment->paystack_reference ?: $this->newReference();
        $callback = route('deal.pay.callback', [$deal->access_token, $payment->id]);

        $result = $service->initializeTransaction(
            $deal->email,
            (int) round($amount * 100),
            $reference,
            $callback,
            [
                'deal_id' => $deal->id,
                'payment_id' => $payment->id,
                'kind' => $kind,
            ],
        );

        if ($result === null || blank($result['authorization_url'])) {
            throw new RuntimeException('Paystack could not generate a payment link. Check the API keys and try again.');
        }

        $payment->update([
            'amount' => $amount,
            'status' => Payment::STATUS_PENDING,
            'paystack_reference' => $result['reference'],
            'paystack_access_code' => $result['access_code'],
            'authorization_url' => $result['authorization_url'],
            'failure_reason' => null,
        ]);

        if ($kind === Payment::KIND_UPFRONT && in_array($deal->status, [
            Deal::STATUS_DRAFT,
            Deal::STATUS_DOCUMENTS_SENT,
        ], true)) {
            $deal->update(['status' => Deal::STATUS_UPFRONT_PENDING]);
        }

        if ($kind === Payment::KIND_DELIVERY) {
            $deal->update(['status' => Deal::STATUS_DELIVERY_PENDING]);
        }

        return $payment->refresh();
    }

    public function markDelivered(Deal $deal): void
    {
        $deal->update([
            'status' => Deal::STATUS_DELIVERY_PENDING,
            'delivered_at' => now(),
        ]);
    }

    public function cancel(Deal $deal): void
    {
        $deal->update([
            'status' => Deal::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark a payment as paid: creates the receipt, advances the deal and
     * triggers receipts + onboarding.
     *
     * Idempotent, and idempotent under concurrency rather than merely in
     * sequence. Three separate routes reach this — the webhook, the browser
     * return callback, and the reconciliation cron — and Paystack's own retry
     * policy means the webhook can deliver the same charge repeatedly. The old
     * version read `isPaid()` and then wrote, which two simultaneous arrivals
     * could both pass, producing a second receipt attempt and a duplicate set
     * of emails. The row is now locked for the duration and the paid transition
     * is a conditional UPDATE, so the database decides which arrival wins
     * rather than the order two requests happened to interleave in.
     *
     * @param  string|null  $chargedAt  When the gateway says the money settled,
     *                                   which is not when we were told about it.
     * @param  string  $via  Which route confirmed this: webhook | callback | reconcile.
     */
    public function markPaymentPaid(
        Payment $payment,
        ?string $chargedAt = null,
        string $via = 'callback',
    ): Payment {
        $settledAt = $this->parseChargedAt($chargedAt);

        // The side effects that must happen exactly once. Collected inside the
        // transaction and dispatched after it commits, so a rollback cannot
        // leave a client told they paid for a payment the database then denied.
        $shouldNotify = false;
        $shouldOnboard = false;

        $dealId = DB::transaction(function () use ($payment, $via, $settledAt, &$shouldNotify, &$shouldOnboard) {
            /** @var Payment|null $locked */
            $locked = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->isPaid()) {
                return null;
            }

            $deal = Deal::query()->whereKey($locked->deal_id)->lockForUpdate()->first();

            if ($deal === null) {
                return null;
            }

            /*
             * Money that arrives for a cancelled deal is not money to quietly
             * drop. The charge settled; the engagement did not. It is left
             * pending with a recorded reason so the refund or the reinstated
             * scope is a decision somebody makes on purpose.
             */
            if ($deal->status === Deal::STATUS_CANCELLED) {
                $locked->forceFill([
                    'gateway_amount' => $locked->gateway_amount,
                    'failure_reason' => 'Charge received for a cancelled deal. Needs a refund or a reinstated scope.',
                ])->save();

                return null;
            }

            $this->createReceipt($locked);

            /*
             * The conditional update is the actual idempotency guarantee. Even
             * with the row locked above, this only takes effect if the row is
             * still unpaid at the moment of writing, so a duplicate arrival
             * finds zero affected rows and knows to stand down.
             */
            $updated = Payment::query()
                ->whereKey($locked->id)
                ->where('status', '!=', Payment::STATUS_PAID)
                ->update([
                    'status' => Payment::STATUS_PAID,
                    'paid_at' => $settledAt,
                    'verified_at' => now(),
                    'confirmed_via' => $via,
                    'failure_reason' => null,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return null;
            }

            if ($locked->kind === Payment::KIND_UPFRONT) {
                if (in_array($deal->status, [
                    Deal::STATUS_DRAFT,
                    Deal::STATUS_DOCUMENTS_SENT,
                    Deal::STATUS_UPFRONT_PENDING,
                ], true)) {
                    $deal->update(['status' => Deal::STATUS_IN_PROGRESS]);
                }

                $shouldNotify = true;
                $shouldOnboard = true;
            } else {
                $deal->update([
                    'status' => Deal::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                $shouldNotify = true;
            }

            // Keep the linked inquiry in sync.
            if ($deal->inquiry !== null && $deal->inquiry->status !== ProjectInquiry::STATUS_CLOSED) {
                $deal->inquiry->markContacted();
            }

            return $locked->id;
        });

        if ($dealId === null) {
            return $payment->refresh();
        }

        $fresh = $payment->refresh();

        if ($shouldNotify) {
            SendPaymentReceipt::dispatch($fresh);
            $this->alertBusiness($fresh);
        }

        if ($shouldOnboard) {
            $deal = $fresh->deal;

            if ($deal !== null && $deal->status === Deal::STATUS_IN_PROGRESS) {
                SendOnboarding::dispatch($deal);
            }
        }

        return $fresh;
    }

    /**
     * Tell the business that money arrived.
     *
     * The client already has a receipt; this is for the other side. The gap that
     * actually costs money is not "did the client know" — it is "did we know".
     * A payment that lands and is quietly never applied is invisible from the
     * client side until they chase it, by which point it is a dispute rather
     * than a receipt.
     *
     * Sits inside the same guarded block as the receipt, so a payment confirmed
     * twice alerts once.
     */
    protected function alertBusiness(Payment $payment): void
    {
        if (! config('paystack.webhook.notify_on_payment', true)) {
            return;
        }

        if (blank(config('paystack.webhook.alert_email'))) {
            Log::warning('Payment landed but no alert address is configured', [
                'payment_id' => $payment->id,
            ]);

            return;
        }

        SendPaymentAlert::dispatch($payment);
    }

    /**
     * Turn the gateway's settlement time into a timestamp, falling back to now.
     *
     * The gateway's value is preferred because a webhook can arrive minutes or
     * hours late and a receipt that claims the money arrived when the server
     * happened to notice is wrong in a way an accountant will find.
     */
    protected function parseChargedAt(?string $chargedAt): Carbon
    {
        if (blank($chargedAt)) {
            return now();
        }

        try {
            return Carbon::parse($chargedAt);
        } catch (\Throwable) {
            Log::warning('Unparseable gateway settlement time; using receipt time instead', [
                'charged_at' => $chargedAt,
            ]);

            return now();
        }
    }

    public function createReceipt(Payment $payment): Receipt
    {
        // firstOrCreate rather than a read followed by an insert, so the
        // uniqueness is enforced by the database rather than by a check that
        // two concurrent arrivals could both pass.
        return Receipt::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'receipt_no' => sprintf('RCP-%s-%04d', now()->year, $payment->id),
                'amount' => $payment->amount,
                'issued_at' => now(),
            ],
        );
    }

    public function handlePaystackChargeSuccess(array $payload): ?Payment
    {
        $reference = data_get($payload, 'data.reference');

        if (blank($reference)) {
            return null;
        }

        $payment = Payment::where('paystack_reference', $reference)->first();

        if ($payment === null) {
            return null;
        }

        return $this->markPaymentPaid(
            $payment,
            data_get($payload, 'data.paid_at'),
            'webhook',
        );
    }

    /**
     * Reconcile pending payments against Paystack. Returns how many were marked paid.
     */
    public function reconcilePendingPayments(): int
    {
        $service = app(PaystackService::class);

        if (! $service->enabled()) {
            return 0;
        }

        $payments = Payment::with('deal')
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('paystack_reference')
            ->get();

        $marked = 0;

        foreach ($payments as $payment) {
            $data = $service->verifyTransaction($payment->paystack_reference);

            if ($data !== null && $service->transactionSucceeded($data)) {
                // The cron closes the gap when a webhook was lost, so it marks
                // paid too — which is why the same idempotency guard covers this
                // path and not only the webhook one.
                $this->markPaymentPaid(
                    $payment,
                    is_string($data['paid_at'] ?? null) ? $data['paid_at'] : null,
                    'reconcile',
                );
                $marked++;
            }
        }

        return $marked;
    }

    public function renderDocumentContent(DealDocument $document): string
    {
        $content = $document->content;

        if (blank($content) && $document->type === DealDocument::TYPE_TEMPLATE) {
            $content = $this->defaultTemplateContent($document->template_key, $document->deal);
        }

        if (blank($content)) {
            return '';
        }

        return $this->renderPlaceholders((string) $content, $document->deal);
    }

    protected function renderPlaceholders(string $content, Deal $deal): string
    {
        $vars = [
            '{{ client_name }}' => $deal->name,
            '{{ client_company }}' => $deal->company ?: $deal->name,
            '{{ client_email }}' => $deal->email,
            '{{ client_phone }}' => $deal->phone ?: '—',
            '{{ project_title }}' => $deal->project_title ?: 'your project',
            '{{ project_type }}' => $deal->project_type ?: 'digital project',
            '{{ scope }}' => $deal->scope ?: '—',
            '{{ start_date }}' => optional($deal->start_date)->format('F j, Y') ?: 'To be confirmed',
            '{{ duration }}' => $deal->duration_label ?: '—',
            '{{ milestones }}' => $deal->milestones ?: '—',
            '{{ amount }}' => '₦'.number_format((float) $deal->amount, 2),
            '{{ upfront_amount }}' => '₦'.number_format($this->upfrontAmount($deal), 2),
            '{{ upfront_percent }}' => $deal->upfront_percent.'%',
            '{{ delivery_amount }}' => '₦'.number_format($this->deliveryAmount($deal), 2),
            '{{ delivery_percent }}' => $deal->delivery_percent.'%',
            '{{ portal_url }}' => $deal->portalUrl(),
        ];

        return strtr($content, $vars);
    }

    public function defaultTemplateContent(?string $key, Deal $deal): string
    {
        return match ($key) {
            DealDocument::KEY_PROPOSAL => implode("\n", [
                'PROJECT PROPOSAL',
                '',
                'Prepared for {{ client_name }} ({{ client_company }})',
                'Project: {{ project_title }}',
                '',
                'Scope',
                '{{ scope }}',
                '',
                'Investment',
                'Total project fee: {{ amount }}',
                'Upfront ({{ upfront_percent }}): {{ upfront_amount }} — due before work begins.',
                'Balance ({{ delivery_percent }}): {{ delivery_amount }} — due on delivery.',
                '',
                'Accepted by: ______________________    Date: _______________',
            ]),
            DealDocument::KEY_TIMELINE => implode("\n", [
                'PROJECT TIMELINE',
                '',
                'Project: {{ project_title }}',
                'Expected start date: {{ start_date }}',
                'Estimated duration: {{ duration }}',
                '',
                'Milestones',
                '{{ milestones }}',
                '',
                'Dates are estimates. We will communicate any shifts in the kickoff call.',
            ]),
            DealDocument::KEY_TERMS => implode("\n", [
                'TERMS OF ENGAGEMENT',
                '',
                '1. Payment is split {{ upfront_percent }}/{{ delivery_percent }}: {{ upfront_amount }} upfront and {{ delivery_amount }} on delivery.',
                '2. Work begins once the upfront payment clears.',
                '3. The client retains full ownership of the final deliverables after full payment.',
                '4. The balance is due before final source files and handover.',
                '5. Either party may terminate with written notice; paid amounts are non-refundable once work has started.',
                '',
                'Questions? Reply to the confirmation email or contact us through the portal.',
            ]),
            default => '',
        };
    }
}

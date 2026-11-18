<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealDocument;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\DealWorkflowService;
use App\Services\PaystackService;
use App\Services\PaystackWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DealPortalController extends Controller
{
    public function show(Deal $deal)
    {
        $workflow = app(DealWorkflowService::class);

        // Both collections are rendered in loops that touch a relation:
        // renderDocumentContent() reads $document->deal, and the receipt list
        // filters on $payment->receipt. Both are loaded here rather than lazily
        // so the portal costs three queries instead of two plus one per row.
        $documents = $deal->documents()
            ->with('deal')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($documents->isEmpty()) {
            $documents = $this->defaultDocuments($deal);
        }

        $payments = $deal->payments()->with('receipt')->orderBy('id')->get();

        return view('portal.show', [
            'deal' => $deal,
            'documents' => $documents,
            'payments' => $payments,
            'workflow' => $workflow,
        ]);
    }

    /**
     * @return Collection<int, DealDocument>
     */
    protected function defaultDocuments(Deal $deal): Collection
    {
        $titles = [
            DealDocument::KEY_PROPOSAL => 'Project Proposal',
            DealDocument::KEY_TIMELINE => 'Project Timeline',
            DealDocument::KEY_TERMS => 'Terms of Engagement',
        ];

        $docs = [];

        $order = 0;

        foreach ([DealDocument::KEY_PROPOSAL, DealDocument::KEY_TIMELINE, DealDocument::KEY_TERMS] as $key) {
            $doc = new DealDocument([
                'type' => DealDocument::TYPE_TEMPLATE,
                'template_key' => $key,
                'title' => $titles[$key],
                'sort_order' => $order++,
                'is_active' => true,
            ]);
            $doc->setRelation('deal', $deal);
            $docs[] = $doc;
        }

        return collect($docs);
    }

    public function pay(Deal $deal, Payment $payment)
    {
        abort_if($payment->deal_id !== $deal->id, 404);

        if ($payment->isPaid()) {
            return redirect()->route('deal.portal', $deal->access_token)
                ->with('notice', 'This payment has already been received. Thank you!');
        }

        $url = $payment->authorization_url;

        if (blank($url)) {
            try {
                $payment = app(DealWorkflowService::class)->generatePaymentLink($deal, $payment->kind);
                $url = $payment->authorization_url;
            } catch (\RuntimeException $e) {
                return redirect()->route('deal.portal', $deal->access_token)
                    ->with('error', 'Payment link unavailable right now. Please try again in a moment.');
            }
        }

        if (blank($url)) {
            return redirect()->route('deal.portal', $deal->access_token)
                ->with('error', 'Payment link unavailable right now. Please try again in a moment.');
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, Deal $deal, Payment $payment)
    {
        abort_if($payment->deal_id !== $deal->id, 404);

        $reference = $request->query('reference') ?? $payment->paystack_reference;

        $service = app(PaystackService::class);
        $verified = $reference ? $service->verifyTransaction($reference) : null;

        if ($verified !== null && $service->transactionSucceeded($verified)) {
            app(DealWorkflowService::class)->markPaymentPaid($payment);

            return redirect()->route('deal.portal', $deal->access_token)
                ->with('success', 'Payment received. Your receipt has been generated and emailed to you.');
        }

        return redirect()->route('deal.portal', $deal->access_token)
            ->with('error', 'We could not confirm the payment. If you paid, it will reflect automatically in a few minutes.');
    }

    public function receipt(Deal $deal, Receipt $receipt)
    {
        abort_if($receipt->payment->deal_id !== $deal->id, 404);

        return view('portal.receipt', [
            'deal' => $deal,
            'payment' => $receipt->payment,
            'receipt' => $receipt,
        ]);
    }

    /**
     * Paystack webhook.
     *
     * Reduced to a hand-off on purpose. Everything that decides whether this
     * request may be believed — the source allowlist in middleware, the
     * signature, the dedupe key, the amount — happens before this method, and
     * everything that changes a payment happens after it, on a queue. All that
     * is left here is asking the service to take the event.
     *
     * The reason is the gateway's retry policy: 30 seconds per attempt, then
     * four retries three minutes apart, then hourly for 72 hours. Work done
     * inline risks the timeout, and a timeout costs 72 hours of duplicate
     * deliveries to process.
     */
    public function webhook(Request $request): JsonResponse
    {
        return app(PaystackWebhookService::class)->handle($request);
    }
}

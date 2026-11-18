<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key', '');
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    public function enabled(): bool
    {
        return $this->secretKey !== '';
    }

    /**
     * Initialize a Paystack hosted-checkout transaction.
     *
     * @param  array<string, mixed>  $payload
     * @return array{authorization_url: string, access_code: string, reference: string}|null
     */
    public function initializeTransaction(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()->post($this->baseUrl.'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => (object) $metadata,
            ]);

            $data = data_get($response->json(), 'data');

            if (! $response->successful() || ! is_array($data)) {
                Log::warning('Paystack initialize failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('Paystack initialize exception', ['error' => $e->getMessage()]);

            return null;
        }

        return [
            'authorization_url' => (string) ($data['authorization_url'] ?? ''),
            'access_code' => (string) ($data['access_code'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
        ];
    }

    /**
     * Verify a transaction by its Paystack reference.
     *
     * @return array<string, mixed>|null Full data payload when the API responds OK
     */
    public function verifyTransaction(string $reference): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->baseUrl.'/transaction/verify/'.rawurlencode($reference));

            if (! $response->successful()) {
                Log::warning('Paystack verify failed', [
                    'reference' => $reference,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json('data');
        } catch (\Throwable $e) {
            Log::warning('Paystack verify exception', ['reference' => $reference, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function transactionSucceeded(array $data): bool
    {
        return ($data['status'] ?? null) === 'success' && ($data['paid_at'] ?? null) !== null;
    }

    /**
     * Validate the Paystack webhook signature.
     *
     * The HMAC covers the raw request body, so this must be handed the bytes as
     * received — never a re-encoded array, which would not reproduce the digest
     * and would reject every genuine event.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if (! $this->enabled() || blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey);

        return hash_equals($expected, (string) $signature);
    }

    /*
    |--------------------------------------------------------------------------
    | Reading a webhook payload
    |--------------------------------------------------------------------------
    |
    | The gateway's payload shape is its own concern and changes on its own
    | schedule. Every field the payment path needs is read through these, so a
    | renamed key is one edit here rather than a hunt through the workflow, and
    | so nothing downstream has to know that amounts arrive in kobo.
    */

    public function eventName(array $payload): ?string
    {
        $event = $payload['event'] ?? null;

        return is_string($event) ? $event : null;
    }

    public function eventReference(array $payload): ?string
    {
        $reference = data_get($payload, 'data.reference');

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    /**
     * The charged amount, in kobo, exactly as the gateway reported it.
     */
    public function eventAmountKobo(array $payload): ?int
    {
        $amount = data_get($payload, 'data.amount');

        return is_numeric($amount) ? (int) $amount : null;
    }

    public function eventCurrency(array $payload): ?string
    {
        $currency = data_get($payload, 'data.currency');

        return is_string($currency) && $currency !== '' ? strtoupper($currency) : null;
    }

    public function eventStatus(array $payload): ?string
    {
        $status = data_get($payload, 'data.status');

        return is_string($status) ? $status : null;
    }

    /**
     * The moment the gateway says the money settled, which is not necessarily
     * when we were told about it — a webhook can arrive minutes late, and the
     * receipt should carry the real time rather than the delivery time.
     */
    public function eventChargedAt(array $payload): ?string
    {
        $chargedAt = data_get($payload, 'data.paid_at');

        return is_string($chargedAt) && $chargedAt !== '' ? $chargedAt : null;
    }

    public function eventAuthorizationCode(array $payload): ?string
    {
        $code = data_get($payload, 'data.authorization.authorization_code');

        return is_string($code) && $code !== '' ? $code : null;
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}

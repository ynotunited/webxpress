<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'kind' => Payment::KIND_UPFRONT,
            'amount' => 700_000,
            'status' => Payment::STATUS_PENDING,
            'paystack_reference' => 'WX-'.strtoupper(fake()->unique()->bothify('????????????????????')),
            'paystack_access_code' => fake()->unique()->bothify('ACCESS########'),
            'authorization_url' => 'https://checkout.paystack.com/test/'.fake()->uuid(),
        ];
    }

    public function delivery(): static
    {
        return $this->state(fn (): array => [
            'kind' => Payment::KIND_DELIVERY,
            'amount' => 300_000,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
            'verified_at' => now(),
            'confirmed_via' => 'webhook',
        ]);
    }

    /**
     * A payment the gateway has never seen — no reference, no link.
     */
    public function withoutGatewayReference(): static
    {
        return $this->state(fn (): array => [
            'paystack_reference' => null,
            'paystack_access_code' => null,
            'authorization_url' => null,
        ]);
    }
}
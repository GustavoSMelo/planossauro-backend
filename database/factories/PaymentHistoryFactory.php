<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\PaymentHistory;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentHistoryFactory extends Factory
{
    protected $model = PaymentHistory::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'payment_date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'card_brand' => $this->faker->creditCardType(),
            'last_four_digits' => $this->faker->numerify('####'),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'status' => PlanStatus::PAID->value,
            'plan_id' => Plans::factory(),
            'user_id' => User::factory(),
            'NFe' => null,
            'stripe_invoice' => null,
            'stripe_product' => null,
            'stripe_subscription' => null,
            'subscription_id' => Subscription::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanStatus::PAID->value,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanStatus::UNPAID->value,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanStatus::CANCELED->value,
        ]);
    }
}

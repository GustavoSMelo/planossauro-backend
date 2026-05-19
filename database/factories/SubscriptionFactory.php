<?php

namespace Database\Factories;

use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'date_verified' => null,
            'next_billing' => null,
            'status' => 'inactive',
            'last_four_digits' => null,
            'user_id' => User::factory(),
            'plans_id' => Plans::factory(),
            'card_brand' => null,
            'price' => 0,
            'stripe_subscription' => null,
            'stripe_user' => null,
            'stripe_price' => null,
            'stripe_product' => null,
            'stripe_subscription_item' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'stripe_subscription' => 'sub_test123',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'price' => 99.00,
                'max_clinics' => 1,
                'max_users' => 5,
                'status' => true,
            ],
            [
                'name' => 'Growth',
                'price' => 249.00,
                'max_clinics' => 3,
                'max_users' => 20,
                'status' => true,
            ],
            [
                'name' => 'Enterprise',
                'price' => 599.00,
                'max_clinics' => 10,
                'max_users' => 100,
                'status' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}

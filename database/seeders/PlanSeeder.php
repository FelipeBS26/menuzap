<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planos definidos na Fase 1. O tenant precisa de um plan_id desde a criação
 * (coluna NOT NULL) — 'starter' é o plano padrão do registro/trial de 14 dias.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'price_cents' => 0,
                'max_products' => 20, 'max_categories' => 3,
                'features' => ['custom_domain' => false, 'qr_code' => false, 'analytics' => false],
            ],
            [
                'name' => 'Básico', 'slug' => 'basico', 'price_cents' => 2990,
                'max_products' => 50, 'max_categories' => null,
                'features' => ['custom_domain' => false, 'qr_code' => false, 'analytics' => false],
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'price_cents' => 4990,
                'max_products' => null, 'max_categories' => null,
                'features' => ['custom_domain' => true, 'qr_code' => true, 'analytics' => false],
            ],
            [
                'name' => 'Premium', 'slug' => 'premium', 'price_cents' => 7990,
                'max_products' => null, 'max_categories' => null,
                'features' => ['custom_domain' => true, 'qr_code' => true, 'analytics' => true, 'white_label' => true],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
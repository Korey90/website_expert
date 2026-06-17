<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_prices')) {
            Schema::create('plan_prices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('currency', 3);
                $table->string('interval', 20);
                $table->unsignedInteger('amount_minor')->default(0);
                $table->string('stripe_price_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['plan_id', 'currency', 'interval'], 'plan_prices_plan_currency_interval_unique');
                $table->index(['currency', 'interval']);
                $table->index('stripe_price_id');
            });
        }

        $this->backfillGbpPrices();
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }

    private function backfillGbpPrices(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_prices')) {
            return;
        }

        $now = now();
        $rows = [];

        foreach (DB::table('plans')->get() as $plan) {
            $rows[] = [
                'plan_id' => $plan->id,
                'currency' => 'GBP',
                'interval' => 'monthly',
                'amount_minor' => (int) ($plan->price_monthly ?? 0),
                'stripe_price_id' => $plan->stripe_price_id_monthly ?? null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $rows[] = [
                'plan_id' => $plan->id,
                'currency' => 'GBP',
                'interval' => 'yearly',
                'amount_minor' => (int) ($plan->price_yearly ?? 0),
                'stripe_price_id' => $plan->stripe_price_id_yearly ?? null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($rows as $row) {
            DB::table('plan_prices')->updateOrInsert(
                [
                    'plan_id' => $row['plan_id'],
                    'currency' => $row['currency'],
                    'interval' => $row['interval'],
                ],
                $row,
            );
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            if (! Schema::hasColumn('businesses', 'plan_price_id')) {
                $table->foreignId('plan_price_id')
                    ->nullable()
                    ->after('plan')
                    ->constrained('plan_prices')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('businesses', 'stripe_subscription_currency')) {
                $table->string('stripe_subscription_currency', 3)->nullable()->after('stripe_subscription_status');
            }

            if (! Schema::hasColumn('businesses', 'stripe_subscription_interval')) {
                $table->string('stripe_subscription_interval', 20)->nullable()->after('stripe_subscription_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            if (Schema::hasColumn('businesses', 'plan_price_id')) {
                $table->dropConstrainedForeignId('plan_price_id');
            }

            if (Schema::hasColumn('businesses', 'stripe_subscription_currency')) {
                $table->dropColumn('stripe_subscription_currency');
            }

            if (Schema::hasColumn('businesses', 'stripe_subscription_interval')) {
                $table->dropColumn('stripe_subscription_interval');
            }
        });
    }
};

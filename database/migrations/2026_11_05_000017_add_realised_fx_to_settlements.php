<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('carrying_amount_gbp', 15, 2)->nullable()->after('amount_gbp');
            $table->decimal('realised_fx_gbp', 15, 2)->default(0)->after('carrying_amount_gbp');
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->decimal('carrying_amount_gbp', 15, 2)->nullable()->after('amount_gbp');
            $table->decimal('realised_fx_gbp', 15, 2)->default(0)->after('carrying_amount_gbp');
        });

        Schema::table('purchase_credit_refunds', function (Blueprint $table): void {
            $table->decimal('carrying_amount_gbp', 15, 2)->nullable()->after('amount_gbp');
            $table->decimal('realised_fx_gbp', 15, 2)->default(0)->after('carrying_amount_gbp');
        });

        foreach (['payments', 'expense_payments', 'purchase_credit_refunds'] as $table) {
            DB::table($table)
                ->whereNotNull('amount_gbp')
                ->update([
                    'carrying_amount_gbp' => DB::raw('amount_gbp'),
                    'realised_fx_gbp' => 0,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_credit_refunds', function (Blueprint $table): void {
            $table->dropColumn(['carrying_amount_gbp', 'realised_fx_gbp']);
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->dropColumn(['carrying_amount_gbp', 'realised_fx_gbp']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['carrying_amount_gbp', 'realised_fx_gbp']);
        });
    }
};

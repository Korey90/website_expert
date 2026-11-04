<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table): void {
            $table->string('category', 50)->default('other')->after('ledger_account_id');
            $table->index(['business_id', 'category']);
        });

        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->string('category', 50)->default('other')->after('ledger_account_id');
            $table->index(['business_id', 'category'], 'recurring_expense_items_tenant_category_index');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->dropIndex('recurring_expense_items_tenant_category_index');
            $table->dropColumn('category');
        });

        Schema::table('expense_items', function (Blueprint $table): void {
            $table->dropIndex(['business_id', 'category']);
            $table->dropColumn('category');
        });
    }
};

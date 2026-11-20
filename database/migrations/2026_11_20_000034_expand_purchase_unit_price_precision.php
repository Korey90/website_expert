<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 24, 10)->change();
        });

        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 24, 10)->change();
        });

        Schema::table('purchase_credit_note_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 24, 10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_credit_note_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 15, 2)->change();
        });

        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 15, 2)->change();
        });

        Schema::table('expense_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 15, 2)->change();
        });
    }
};

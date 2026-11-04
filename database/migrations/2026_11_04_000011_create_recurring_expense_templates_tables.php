<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expense_templates', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('supplier_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->string('frequency', 20);
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->timestamp('last_generated_at')->nullable();
            $table->char('currency', 3)->default('GBP');
            $table->unsignedSmallInteger('due_days')->default(30);
            $table->string('payment_method', 50)->nullable();
            $table->text('business_purpose');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'status', 'next_run_date'], 'recurring_expenses_due_index');
        });

        Schema::create('recurring_expense_template_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('recurring_expense_template_id');
            $table->foreignId('tax_code_id')->nullable();
            $table->unsignedBigInteger('ledger_account_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->boolean('billable')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('recurring_expense_template_id', 'recurring_items_template_foreign')
                ->references('id')
                ->on('recurring_expense_templates')
                ->cascadeOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->index(['business_id', 'recurring_expense_template_id'], 'recurring_expense_items_tenant_template_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('recurring_expense_template_id')->nullable()->after('project_id');
            $table->date('recurrence_date')->nullable()->after('recurring_expense_template_id');
            $table->foreign('recurring_expense_template_id', 'expenses_recurring_template_foreign')
                ->references('id')
                ->on('recurring_expense_templates')
                ->nullOnDelete();
            $table->unique(
                ['recurring_expense_template_id', 'recurrence_date'],
                'expenses_recurring_template_date_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropUnique('expenses_recurring_template_date_unique');
            $table->dropForeign('expenses_recurring_template_foreign');
            $table->dropColumn(['recurring_expense_template_id', 'recurrence_date']);
        });

        Schema::dropIfExists('recurring_expense_template_items');
        Schema::dropIfExists('recurring_expense_templates');
    }
};

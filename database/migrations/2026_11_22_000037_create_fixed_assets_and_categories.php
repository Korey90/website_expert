<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_asset_categories', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('code', 32);
            $table->json('name');
            $table->unsignedBigInteger('asset_account_id');
            $table->unsignedBigInteger('accumulated_depreciation_account_id');
            $table->unsignedBigInteger('depreciation_expense_account_id');
            $table->string('default_depreciation_method', 32)->default('straight_line');
            $table->unsignedSmallInteger('default_useful_life_months')->nullable();
            $table->decimal('default_residual_value_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('asset_account_id', 'fa_categories_asset_account_fk')->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->foreign('accumulated_depreciation_account_id', 'fa_categories_accumulated_account_fk')->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->foreign('depreciation_expense_account_id', 'fa_categories_expense_account_fk')->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->unique(['business_id', 'code'], 'fixed_asset_categories_business_code_unique');
            $table->index(['business_id', 'is_active'], 'fixed_asset_categories_business_active_index');
        });

        Schema::create('fixed_assets', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('fixed_asset_category_id')->constrained('fixed_asset_categories')->restrictOnDelete();
            $table->string('number', 40);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('depreciation_method', 32);
            $table->unsignedSmallInteger('useful_life_months')->nullable();
            $table->decimal('residual_value_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'number'], 'fixed_assets_business_number_unique');
            $table->index(['business_id', 'fixed_asset_category_id', 'status'], 'fixed_assets_business_category_status_index');
            $table->index(['business_id', 'serial_number'], 'fixed_assets_business_serial_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('fixed_asset_categories');
    }
};

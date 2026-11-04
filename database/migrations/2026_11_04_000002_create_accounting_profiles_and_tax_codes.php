<?php

use App\Support\Finance\DefaultUkTaxCodes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_profiles', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26)->unique();
            $table->string('entity_type')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('company_number')->nullable();
            $table->json('registered_address')->nullable();
            $table->text('company_utr')->nullable();
            $table->char('functional_currency', 3)->default('GBP');
            $table->string('accounting_basis')->default('accrual');
            $table->date('financial_year_start')->nullable();
            $table->date('financial_year_end')->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->string('vat_number')->nullable();
            $table->string('vat_scheme')->nullable();
            $table->date('vat_effective_from')->nullable();
            $table->date('vat_effective_to')->nullable();
            $table->text('paye_reference')->nullable();
            $table->text('accounts_office_reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();
        });

        Schema::create('tax_codes', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->string('code', 32);
            $table->json('label');
            $table->string('type');
            $table->decimal('rate', 5, 2)->default(0);
            $table->boolean('input_eligible')->default(false);
            $table->boolean('output_eligible')->default(false);
            $table->json('vat_return_mapping')->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();

            $table->unique(['business_id', 'code', 'valid_from']);
            $table->index(['business_id', 'is_active', 'valid_from', 'valid_to']);
        });

        $this->initializeExistingBusinesses();
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('accounting_profiles');
    }

    private function initializeExistingBusinesses(): void
    {
        $now = now();

        DB::table('businesses')
            ->whereNull('deleted_at')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function (object $business) use ($now): void {
                DB::table('accounting_profiles')->insertOrIgnore([
                    'business_id' => $business->id,
                    'legal_name' => $business->name,
                    'functional_currency' => 'GBP',
                    'accounting_basis' => 'accrual',
                    'vat_registered' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (DefaultUkTaxCodes::all() as $taxCode) {
                    $taxCode['business_id'] = $business->id;
                    $taxCode['label'] = json_encode($taxCode['label'], JSON_THROW_ON_ERROR);
                    $taxCode['vat_return_mapping'] = json_encode($taxCode['vat_return_mapping'], JSON_THROW_ON_ERROR);
                    $taxCode['created_at'] = $now;
                    $taxCode['updated_at'] = $now;

                    DB::table('tax_codes')->insertOrIgnore($taxCode);
                }
            });
    }
};

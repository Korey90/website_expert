<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'view_universal_credit_reporting',
        'manage_universal_credit_reporting',
        'export_universal_credit_pack',
    ];

    public function up(): void
    {
        Schema::create('universal_credit_policy_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->unsignedSmallInteger('version');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamp('verified_at');
            $table->json('source_urls');
            $table->json('rules');
            $table->char('rules_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'version'], 'uc_policy_code_version_unique');
            $table->index(['code', 'is_active', 'effective_from', 'effective_to'], 'uc_policy_effective_index');
        });

        Schema::create('universal_credit_profiles', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->boolean('is_enabled')->default(false);
            $table->string('vat_reporting_method', 16)->nullable();
            $table->date('vat_method_effective_from')->nullable();
            $table->unsignedTinyInteger('default_assessment_period_start_day')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_profiles_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('created_by', 'uc_profiles_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'uc_profiles_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('business_id', 'uc_profiles_business_unique');
        });

        Schema::create('universal_credit_assessment_periods', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('universal_credit_profile_id');
            $table->foreignId('policy_version_id');
            $table->string('number', 40);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('status', 16)->default('draft');
            $table->string('vat_reporting_method_snapshot', 16)->nullable();
            $table->json('policy_rules_snapshot');
            $table->char('policy_rules_hash', 64);
            $table->unsignedBigInteger('income_total_pence')->default(0);
            $table->unsignedBigInteger('expense_total_pence')->default(0);
            $table->bigInteger('net_total_pence')->default(0);
            $table->char('source_snapshot_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('lock_reason')->nullable();
            $table->foreignId('reported_by')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->string('report_confirmation_reference', 150)->nullable();
            $table->foreignId('superseded_by_user_id')->nullable();
            $table->foreignId('superseding_period_id')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->text('supersede_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_periods_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('universal_credit_profile_id', 'uc_periods_profile_fk')->references('id')->on('universal_credit_profiles')->restrictOnDelete();
            $table->foreign('policy_version_id', 'uc_periods_policy_fk')->references('id')->on('universal_credit_policy_versions')->restrictOnDelete();
            $table->foreign('created_by', 'uc_periods_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'uc_periods_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by', 'uc_periods_locked_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reported_by', 'uc_periods_reported_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('superseded_by_user_id', 'uc_periods_superseded_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('superseding_period_id', 'uc_periods_superseding_period_fk')->references('id')->on('universal_credit_assessment_periods')->restrictOnDelete();
            $table->unique(['business_id', 'number'], 'uc_periods_business_number_unique');
            $table->index(['business_id', 'date_from', 'date_to'], 'uc_periods_business_dates_index');
            $table->index(['business_id', 'status', 'date_from', 'date_to'], 'uc_periods_business_status_dates_index');
        });

        $this->seedInitialPolicy();
        $this->createPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_credit_assessment_periods');
        Schema::dropIfExists('universal_credit_profiles');
        Schema::dropIfExists('universal_credit_policy_versions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->get()
            ->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedInitialPolicy(): void
    {
        $rules = [
            'schema_version' => 1,
            'income_categories' => [
                'goods_and_services', 'payment_in_kind', 'tips_and_gratuities',
                'business_tax_or_ni_refund', 'taxable_grant_or_subsidy',
                'previously_expensed_asset_sale', 'other_business_income',
                'excluded_personal_or_non_trading', 'review_required',
            ],
            'expense_categories' => [
                'car_or_minicab_simplified_travel', 'other_vehicle_and_business_travel',
                'place_of_business', 'tax_ni_pension_and_vat', 'legal_financial_and_insurance',
                'office_and_equipment', 'staff_and_subcontractors', 'goods_materials_and_work_clothing',
                'payment_in_kind', 'marketing_and_advertising', 'subscriptions_and_training',
                'use_of_home', 'living_at_business_premises', 'other_business_expense',
                'excluded_or_disallowed', 'review_required',
            ],
            'vehicle_mileage' => [
                'car_van_or_other_motor_vehicle' => [
                    'first_band_miles' => 833,
                    'first_band_rate_pence' => 45,
                    'additional_rate_pence' => 25,
                ],
                'motorbike_or_scooter' => ['rate_pence' => 24],
                'actual_costs_with_flat_rate_allowed' => false,
            ],
            'home_working' => [
                'minimum_hours' => 25,
                'bands' => [
                    ['minimum_hours' => 25, 'maximum_hours' => 50, 'amount_pence' => 1000],
                    ['minimum_hours' => 51, 'maximum_hours' => 100, 'amount_pence' => 1800],
                    ['minimum_hours' => 101, 'maximum_hours' => null, 'amount_pence' => 2600],
                ],
                'actual_costs_with_flat_rate_allowed' => false,
            ],
            'living_at_business_premises' => [
                'monthly_deduction_pence' => [
                    'one_person' => 35000,
                    'two_people' => 50000,
                    'three_or_more_people' => 65000,
                ],
            ],
            'business_loan_interest' => ['monthly_limit_pence' => 4100],
            'vat_reporting_methods' => ['inclusive', 'exclusive'],
            'excluded_expenses' => [
                'non_depreciating_assets', 'client_or_supplier_entertainment', 'charity_donations',
                'unrelated_membership_fees', 'private_use', 'normal_clothing', 'depreciation',
                'loan_capital_repayment', 'commuting', 'fines_and_penalties',
            ],
        ];
        $canonical = $this->canonicalize($rules);
        $encoded = json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $now = now();

        DB::table('universal_credit_policy_versions')->insert([
            'code' => 'ni_uc_self_employment',
            'version' => 1,
            'effective_from' => '2026-11-23',
            'effective_to' => null,
            'verified_at' => '2026-11-23 00:00:00',
            'source_urls' => json_encode([
                'https://www.nidirect.gov.uk/articles/report-business-income-and-expenses-if-youre-self-employed',
                'https://www.nidirect.gov.uk/articles/business-expenses-you-can-report-if-youre-self-employed',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'rules' => $encoded,
            'rules_hash' => hash('sha256', $encoded),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($this->permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
};

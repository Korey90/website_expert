<?php

use App\Support\Finance\DefaultUkChartAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('code', 32);
            $table->json('name');
            $table->string('type', 20);
            $table->string('subtype', 50)->nullable();
            $table->string('normal_balance', 6);
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'type', 'is_active']);
            $table->index(['business_id', 'parent_id']);
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->unsignedBigInteger('default_ledger_account_id')->nullable()->after('default_currency');
            $table->foreign('default_ledger_account_id', 'suppliers_default_ledger_foreign')
                ->references('id')->on('ledger_accounts')->nullOnDelete();
        });

        Schema::table('expense_items', function (Blueprint $table): void {
            $table->foreign('ledger_account_id', 'expense_items_ledger_foreign')
                ->references('id')->on('ledger_accounts')->nullOnDelete();
        });

        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->foreign('ledger_account_id', 'recurring_items_ledger_foreign')
                ->references('id')->on('ledger_accounts')->nullOnDelete();
        });

        $this->initializeExistingBusinesses();
    }

    public function down(): void
    {
        Schema::table('recurring_expense_template_items', function (Blueprint $table): void {
            $table->dropForeign('recurring_items_ledger_foreign');
        });
        Schema::table('expense_items', function (Blueprint $table): void {
            $table->dropForeign('expense_items_ledger_foreign');
        });
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropForeign('suppliers_default_ledger_foreign');
            $table->dropColumn('default_ledger_account_id');
        });
        Schema::dropIfExists('ledger_accounts');
    }

    private function initializeExistingBusinesses(): void
    {
        DB::table('businesses')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (string $businessId) => $this->initializeBusiness($businessId));
    }

    private function initializeBusiness(string $businessId): void
    {
        $idsByCode = [];

        foreach (DefaultUkChartAccounts::all() as $account) {
            $parentCode = $account['parent_code'];
            unset($account['parent_code']);

            $idsByCode[$account['code']] = DB::table('ledger_accounts')->insertGetId([
                ...$account,
                'business_id' => $businessId,
                'name' => json_encode($account['name'], JSON_THROW_ON_ERROR),
                'parent_id' => $parentCode ? $idsByCode[$parentCode] : null,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

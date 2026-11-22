<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SOURCE_BANK_ACCOUNT = 'bank_account';

    private const SOURCE_CASH_ACCOUNT = 'cash_account';

    public function up(): void
    {
        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->string('settlement_source', 30)
                ->default(self::SOURCE_CASH_ACCOUNT)
                ->after('bank_account_id');
            $table->index(
                ['business_id', 'settlement_source', 'paid_at'],
                'expense_payments_business_source_paid_index',
            );
        });

        DB::table('expense_payments')
            ->whereNotNull('bank_account_id')
            ->update(['settlement_source' => self::SOURCE_BANK_ACCOUNT]);

        $this->createOwnerCapitalAccounts();
    }

    public function down(): void
    {
        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->dropIndex('expense_payments_business_source_paid_index');
            $table->dropColumn('settlement_source');
        });

        DB::table('ledger_accounts')
            ->where('code', '3300')
            ->where('subtype', 'owner_capital')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('journal_lines')
                ->whereColumn('journal_lines.ledger_account_id', 'ledger_accounts.id'))
            ->delete();
    }

    private function createOwnerCapitalAccounts(): void
    {
        $businessIds = DB::table('businesses')->orderBy('id')->pluck('id');

        foreach ($businessIds as $businessId) {
            if (DB::table('ledger_accounts')
                ->where('business_id', $businessId)
                ->where('code', '3300')
                ->exists()) {
                continue;
            }

            $parentId = DB::table('ledger_accounts')
                ->where('business_id', $businessId)
                ->where('code', '3000')
                ->value('id');

            if (! $parentId) {
                continue;
            }

            DB::table('ledger_accounts')->insert([
                'business_id' => $businessId,
                'code' => '3300',
                'name' => json_encode([
                    'en' => "Owner's capital introduced",
                    'pl' => 'Kapitał wniesiony przez właściciela',
                    'pt' => 'Capital introduzido pelo proprietário',
                ], JSON_THROW_ON_ERROR),
                'type' => 'equity',
                'subtype' => 'owner_capital',
                'normal_balance' => 'credit',
                'parent_id' => $parentId,
                'is_postable' => true,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

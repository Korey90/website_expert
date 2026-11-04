<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('tax_point')->nullable()->after('issue_date');
            $this->addGbpSnapshotColumns($table, 'currency');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $this->addGbpSnapshotColumns($table, 'currency');
        });

        foreach (['invoice_items', 'quote_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->decimal('net_amount_gbp', 15, 2)->nullable()->after('net_amount');
                $table->decimal('vat_amount_gbp', 15, 2)->nullable()->after('vat_amount');
                $table->decimal('gross_amount_gbp', 15, 2)->nullable()->after('gross_amount');
            });
        }

        $this->backfillGbpDocuments();
    }

    public function down(): void
    {
        foreach (['quote_items', 'invoice_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['net_amount_gbp', 'vat_amount_gbp', 'gross_amount_gbp']);
            });
        }

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropColumn($this->gbpSnapshotColumnNames());
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['tax_point', ...$this->gbpSnapshotColumnNames()]);
        });
    }

    private function addGbpSnapshotColumns(Blueprint $table, string $after): void
    {
        $table->decimal('gbp_exchange_rate', 18, 8)->nullable()->after($after);
        $table->string('exchange_rate_source', 100)->nullable()->after('gbp_exchange_rate');
        $table->date('exchange_rate_date')->nullable()->after('exchange_rate_source');
        $table->decimal('subtotal_gbp', 15, 2)->nullable()->after('exchange_rate_date');
        $table->decimal('vat_amount_gbp', 15, 2)->nullable()->after('subtotal_gbp');
        $table->decimal('total_gbp', 15, 2)->nullable()->after('vat_amount_gbp');
    }

    /** @return list<string> */
    private function gbpSnapshotColumnNames(): array
    {
        return [
            'gbp_exchange_rate',
            'exchange_rate_source',
            'exchange_rate_date',
            'subtotal_gbp',
            'vat_amount_gbp',
            'total_gbp',
        ];
    }

    private function backfillGbpDocuments(): void
    {
        DB::table('invoices')
            ->where(fn ($query) => $query->whereNull('currency')->orWhere('currency', 'GBP'))
            ->update([
                'tax_point' => DB::raw('issue_date'),
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => DB::raw('issue_date'),
                'subtotal_gbp' => DB::raw('subtotal'),
                'vat_amount_gbp' => DB::raw('vat_amount'),
                'total_gbp' => DB::raw('total'),
            ]);

        DB::table('quotes')
            ->where(fn ($query) => $query->whereNull('currency')->orWhere('currency', 'GBP'))
            ->update([
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => DB::raw('DATE(created_at)'),
                'subtotal_gbp' => DB::raw('subtotal'),
                'vat_amount_gbp' => DB::raw('vat_amount'),
                'total_gbp' => DB::raw('total'),
            ]);

        $this->copyGbpItemAmounts('invoice_items', 'invoices', 'invoice_id');
        $this->copyGbpItemAmounts('quote_items', 'quotes', 'quote_id');
    }

    private function copyGbpItemAmounts(string $itemsTable, string $documentsTable, string $foreignKey): void
    {
        DB::table($itemsTable)
            ->whereExists(function ($query) use ($itemsTable, $documentsTable, $foreignKey): void {
                $query->selectRaw('1')
                    ->from($documentsTable)
                    ->whereColumn("{$documentsTable}.id", "{$itemsTable}.{$foreignKey}")
                    ->where(fn ($currency) => $currency->whereNull("{$documentsTable}.currency")
                        ->orWhere("{$documentsTable}.currency", 'GBP'));
            })
            ->update([
                'net_amount_gbp' => DB::raw('net_amount'),
                'vat_amount_gbp' => DB::raw('vat_amount'),
                'gross_amount_gbp' => DB::raw('gross_amount'),
            ]);
    }
};

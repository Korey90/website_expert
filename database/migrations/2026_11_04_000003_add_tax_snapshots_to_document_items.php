<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['invoice_items', 'quote_items'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->char('business_id', 26)
                    ->nullable()
                    ->after('id')
                    ->index();
                $table->foreign('business_id')
                    ->references('id')
                    ->on('businesses')
                    ->nullOnDelete();
                $table->foreignId('tax_code_id')
                    ->nullable()
                    ->after('details')
                    ->constrained('tax_codes')
                    ->nullOnDelete();
                $table->date('supply_date')->nullable()->after('tax_code_id');
                $table->decimal('net_amount', 12, 2)->nullable()->after('amount');
                $table->decimal('vat_rate_snapshot', 5, 2)->nullable()->after('net_amount');
                $table->decimal('vat_amount', 12, 2)->nullable()->after('vat_rate_snapshot');
                $table->decimal('gross_amount', 12, 2)->nullable()->after('vat_amount');
            });
        }

        $this->backfillBusinessId('invoice_items', 'invoices', 'invoice_id');
        $this->backfillBusinessId('quote_items', 'quotes', 'quote_id');
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['tax_code_id']);
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
                $table->dropColumn([
                    'business_id',
                    'tax_code_id',
                    'supply_date',
                    'net_amount',
                    'vat_rate_snapshot',
                    'vat_amount',
                    'gross_amount',
                ]);
            });
        }
    }

    private function backfillBusinessId(string $table, string $parentTable, string $foreignKey): void
    {
        DB::table($table)
            ->whereNull('business_id')
            ->whereExists(function ($query) use ($table, $parentTable, $foreignKey): void {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->whereColumn("{$parentTable}.id", "{$table}.{$foreignKey}")
                    ->whereNotNull("{$parentTable}.business_id");
            })
            ->update([
                'business_id' => DB::raw(
                    "(SELECT {$parentTable}.business_id FROM {$parentTable} WHERE {$parentTable}.id = {$table}.{$foreignKey})"
                ),
            ]);
    }
};

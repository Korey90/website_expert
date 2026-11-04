<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const TABLES = [
        'contacts',
        'projects',
        'quotes',
        'invoices',
        'payments',
    ];

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
            });
        }

        $this->backfillFromParent('contacts', 'clients', 'client_id');
        $this->backfillFromParent('projects', 'clients', 'client_id');
        $this->backfillFromParent('quotes', 'clients', 'client_id');
        $this->backfillFromParent('invoices', 'clients', 'client_id');
        $this->backfillFromParent('payments', 'invoices', 'invoice_id');
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }

    private function backfillFromParent(string $table, string $parentTable, string $foreignKey): void
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

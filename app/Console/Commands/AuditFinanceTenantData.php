<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditFinanceTenantData extends Command
{
    protected $signature = 'finance:audit-tenants {--json : Output the report as JSON}';

    protected $description = 'Audit finance records for missing or mismatched business ownership';

    /**
     * @var array<string, array{parent: string, foreign_key: string}>
     */
    private const RELATIONS = [
        'contacts' => ['parent' => 'clients', 'foreign_key' => 'client_id'],
        'projects' => ['parent' => 'clients', 'foreign_key' => 'client_id'],
        'quotes' => ['parent' => 'clients', 'foreign_key' => 'client_id'],
        'invoices' => ['parent' => 'clients', 'foreign_key' => 'client_id'],
        'payments' => ['parent' => 'invoices', 'foreign_key' => 'invoice_id'],
        'invoice_items' => ['parent' => 'invoices', 'foreign_key' => 'invoice_id'],
        'quote_items' => ['parent' => 'quotes', 'foreign_key' => 'quote_id'],
        'credit_notes' => ['parent' => 'invoices', 'foreign_key' => 'invoice_id'],
        'credit_note_items' => ['parent' => 'credit_notes', 'foreign_key' => 'credit_note_id'],
    ];

    public function handle(): int
    {
        $report = [];

        foreach (self::RELATIONS as $table => $relation) {
            $parent = $relation['parent'];
            $foreignKey = $relation['foreign_key'];

            $baseQuery = DB::table($table)
                ->leftJoin($parent, "{$parent}.id", '=', "{$table}.{$foreignKey}")
                ->where(function ($query) use ($table, $parent): void {
                    $query->whereNull("{$table}.business_id")
                        ->orWhereNull("{$parent}.business_id")
                        ->orWhereColumn("{$table}.business_id", '!=', "{$parent}.business_id");
                });

            $report[$table] = [
                'invalid_count' => (clone $baseQuery)->count(),
                'sample_ids' => (clone $baseQuery)
                    ->limit(20)
                    ->pluck("{$table}.id")
                    ->all(),
            ];
        }

        $invalidCount = array_sum(array_column($report, 'invalid_count'));

        if ($this->option('json')) {
            $this->line(json_encode([
                'valid' => $invalidCount === 0,
                'invalid_count' => $invalidCount,
                'tables' => $report,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $rows = collect($report)
                ->map(fn (array $result, string $table): array => [
                    $table,
                    $result['invalid_count'],
                    implode(', ', $result['sample_ids']),
                ])
                ->values()
                ->all();

            $this->table(['Table', 'Invalid records', 'Sample IDs'], $rows);

            if ($invalidCount === 0) {
                $this->info('Finance tenant audit passed.');
            } else {
                $this->error("Finance tenant audit failed: {$invalidCount} invalid record(s).");
            }
        }

        return $invalidCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}

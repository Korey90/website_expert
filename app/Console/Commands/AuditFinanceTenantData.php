<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditFinanceTenantData extends Command
{
    protected $signature = 'finance:audit-tenants {--json : Output the report as JSON}';

    protected $description = 'Audit finance records for missing or mismatched business ownership';

    /**
     * @var array<string, array{parent: string, foreign_key: string, nullable_parent?: bool}>
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
        'expenses' => ['parent' => 'suppliers', 'foreign_key' => 'supplier_id', 'nullable_parent' => true],
        'expense_items' => ['parent' => 'expenses', 'foreign_key' => 'expense_id'],
        'purchase_credit_notes' => ['parent' => 'expenses', 'foreign_key' => 'expense_id'],
        'purchase_credit_note_items' => ['parent' => 'purchase_credit_notes', 'foreign_key' => 'purchase_credit_note_id'],
        'purchase_credit_refunds' => ['parent' => 'purchase_credit_notes', 'foreign_key' => 'purchase_credit_note_id'],
        'recurring_expense_templates' => ['parent' => 'suppliers', 'foreign_key' => 'supplier_id', 'nullable_parent' => true],
        'recurring_expense_template_items' => ['parent' => 'recurring_expense_templates', 'foreign_key' => 'recurring_expense_template_id'],
        'journal_entries' => ['parent' => 'accounting_periods', 'foreign_key' => 'accounting_period_id'],
        'journal_lines' => ['parent' => 'journal_entries', 'foreign_key' => 'journal_entry_id'],
    ];

    /** @var array<string, array{table: string, parent: string, foreign_key: string}> */
    private const OPTIONAL_TENANT_RELATIONS = [
        'suppliers.default_ledger_account_id' => ['table' => 'suppliers', 'parent' => 'ledger_accounts', 'foreign_key' => 'default_ledger_account_id'],
        'expense_items.ledger_account_id' => ['table' => 'expense_items', 'parent' => 'ledger_accounts', 'foreign_key' => 'ledger_account_id'],
        'recurring_expense_template_items.ledger_account_id' => ['table' => 'recurring_expense_template_items', 'parent' => 'ledger_accounts', 'foreign_key' => 'ledger_account_id'],
        'journal_lines.ledger_account_id' => ['table' => 'journal_lines', 'parent' => 'ledger_accounts', 'foreign_key' => 'ledger_account_id'],
        'journal_lines.tax_code_id' => ['table' => 'journal_lines', 'parent' => 'tax_codes', 'foreign_key' => 'tax_code_id'],
        'journal_lines.client_id' => ['table' => 'journal_lines', 'parent' => 'clients', 'foreign_key' => 'client_id'],
        'journal_lines.supplier_id' => ['table' => 'journal_lines', 'parent' => 'suppliers', 'foreign_key' => 'supplier_id'],
        'journal_lines.project_id' => ['table' => 'journal_lines', 'parent' => 'projects', 'foreign_key' => 'project_id'],
    ];

    public function handle(): int
    {
        $report = [];

        foreach (self::RELATIONS as $table => $relation) {
            $parent = $relation['parent'];
            $foreignKey = $relation['foreign_key'];

            $baseQuery = DB::table($table)
                ->leftJoin($parent, "{$parent}.id", '=', "{$table}.{$foreignKey}")
                ->where(function ($query) use ($table, $parent, $foreignKey, $relation): void {
                    $query->whereNull("{$table}.business_id");

                    if ($relation['nullable_parent'] ?? false) {
                        $query->orWhere(function ($parentMismatch) use ($table, $parent, $foreignKey): void {
                            $parentMismatch->whereNotNull("{$table}.{$foreignKey}")
                                ->where(function ($mismatch) use ($table, $parent): void {
                                    $mismatch->whereNull("{$parent}.business_id")
                                        ->orWhereColumn("{$table}.business_id", '!=', "{$parent}.business_id");
                                });
                        });
                    } else {
                        $query->orWhereNull("{$parent}.business_id")
                            ->orWhereColumn("{$table}.business_id", '!=', "{$parent}.business_id");
                    }
                });

            $report[$table] = [
                'invalid_count' => (clone $baseQuery)->count(),
                'sample_ids' => (clone $baseQuery)
                    ->limit(20)
                    ->pluck("{$table}.id")
                    ->all(),
            ];
        }

        $ledgerQuery = DB::table('ledger_accounts as account')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'account.business_id')
            ->leftJoin('ledger_accounts as parent', 'parent.id', '=', 'account.parent_id')
            ->where(function ($query): void {
                $query->whereNull('account.business_id')
                    ->orWhereNull('owner.id')
                    ->orWhere(function ($parentMismatch): void {
                        $parentMismatch->whereNotNull('account.parent_id')
                            ->where(function ($mismatch): void {
                                $mismatch->whereNull('parent.id')
                                    ->orWhereColumn('account.business_id', '!=', 'parent.business_id');
                            });
                    });
            });
        $report['ledger_accounts'] = [
            'invalid_count' => (clone $ledgerQuery)->count(),
            'sample_ids' => (clone $ledgerQuery)->limit(20)->pluck('account.id')->all(),
        ];

        $periodQuery = DB::table('accounting_periods as period')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'period.business_id')
            ->where(fn ($query) => $query->whereNull('period.business_id')->orWhereNull('owner.id'));
        $report['accounting_periods'] = [
            'invalid_count' => (clone $periodQuery)->count(),
            'sample_ids' => (clone $periodQuery)->limit(20)->pluck('period.id')->all(),
        ];

        foreach (self::OPTIONAL_TENANT_RELATIONS as $label => $relation) {
            $table = $relation['table'];
            $parent = $relation['parent'];
            $foreignKey = $relation['foreign_key'];
            $query = DB::table($table)
                ->leftJoin($parent, "{$parent}.id", '=', "{$table}.{$foreignKey}")
                ->whereNotNull("{$table}.{$foreignKey}")
                ->where(function ($mismatch) use ($table, $parent): void {
                    $mismatch->whereNull("{$parent}.id")
                        ->orWhereColumn("{$table}.business_id", '!=', "{$parent}.business_id");
                });
            $report[$label] = [
                'invalid_count' => (clone $query)->count(),
                'sample_ids' => (clone $query)->limit(20)->pluck("{$table}.id")->all(),
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

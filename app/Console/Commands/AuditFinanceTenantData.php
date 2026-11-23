<?php

namespace App\Console\Commands;

use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Services\Finance\UniversalCreditCalculationHashService;
use App\Services\Finance\UniversalCreditPolicyHashService;
use App\Services\Finance\VatReturnSnapshotService;
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
        'expense_payments' => ['parent' => 'expenses', 'foreign_key' => 'expense_id'],
        'fixed_assets' => ['parent' => 'fixed_asset_categories', 'foreign_key' => 'fixed_asset_category_id'],
        'fixed_asset_transactions' => ['parent' => 'fixed_assets', 'foreign_key' => 'fixed_asset_id'],
        'fixed_asset_depreciation_entries' => ['parent' => 'fixed_assets', 'foreign_key' => 'fixed_asset_id'],
        'purchase_credit_notes' => ['parent' => 'expenses', 'foreign_key' => 'expense_id'],
        'purchase_credit_note_items' => ['parent' => 'purchase_credit_notes', 'foreign_key' => 'purchase_credit_note_id'],
        'purchase_credit_refunds' => ['parent' => 'purchase_credit_notes', 'foreign_key' => 'purchase_credit_note_id'],
        'recurring_expense_templates' => ['parent' => 'suppliers', 'foreign_key' => 'supplier_id', 'nullable_parent' => true],
        'recurring_expense_template_items' => ['parent' => 'recurring_expense_templates', 'foreign_key' => 'recurring_expense_template_id'],
        'journal_entries' => ['parent' => 'accounting_periods', 'foreign_key' => 'accounting_period_id'],
        'journal_lines' => ['parent' => 'journal_entries', 'foreign_key' => 'journal_entry_id'],
        'vat_adjustments' => ['parent' => 'tax_codes', 'foreign_key' => 'tax_code_id'],
        'bank_accounts' => ['parent' => 'ledger_accounts', 'foreign_key' => 'ledger_account_id'],
        'bank_statement_imports' => ['parent' => 'bank_accounts', 'foreign_key' => 'bank_account_id'],
        'bank_transactions' => ['parent' => 'bank_accounts', 'foreign_key' => 'bank_account_id'],
        'bank_reconciliations' => ['parent' => 'bank_transactions', 'foreign_key' => 'bank_transaction_id'],
        'bank_reconciliation_allocations' => ['parent' => 'bank_reconciliations', 'foreign_key' => 'bank_reconciliation_id'],
        'hmrc_vat_submissions' => ['parent' => 'vat_returns', 'foreign_key' => 'vat_return_id'],
        'hmrc_vat_submission_events' => ['parent' => 'hmrc_vat_submissions', 'foreign_key' => 'hmrc_vat_submission_id'],
        'universal_credit_assessment_periods' => ['parent' => 'universal_credit_profiles', 'foreign_key' => 'universal_credit_profile_id'],
        'universal_credit_manual_entries' => ['parent' => 'universal_credit_assessment_periods', 'foreign_key' => 'assessment_period_id'],
        'universal_credit_report_items' => ['parent' => 'universal_credit_assessment_periods', 'foreign_key' => 'assessment_period_id'],
        'universal_credit_simplified_expenses' => ['parent' => 'universal_credit_assessment_periods', 'foreign_key' => 'assessment_period_id'],
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
        'vat_adjustments.control_ledger_account_id' => ['table' => 'vat_adjustments', 'parent' => 'ledger_accounts', 'foreign_key' => 'control_ledger_account_id'],
        'vat_adjustments.contra_ledger_account_id' => ['table' => 'vat_adjustments', 'parent' => 'ledger_accounts', 'foreign_key' => 'contra_ledger_account_id'],
        'bank_transactions.bank_statement_import_id' => ['table' => 'bank_transactions', 'parent' => 'bank_statement_imports', 'foreign_key' => 'bank_statement_import_id'],
        'payments.bank_account_id' => ['table' => 'payments', 'parent' => 'bank_accounts', 'foreign_key' => 'bank_account_id'],
        'expense_payments.bank_account_id' => ['table' => 'expense_payments', 'parent' => 'bank_accounts', 'foreign_key' => 'bank_account_id'],
        'fixed_asset_categories.asset_account_id' => ['table' => 'fixed_asset_categories', 'parent' => 'ledger_accounts', 'foreign_key' => 'asset_account_id'],
        'fixed_asset_categories.accumulated_depreciation_account_id' => ['table' => 'fixed_asset_categories', 'parent' => 'ledger_accounts', 'foreign_key' => 'accumulated_depreciation_account_id'],
        'fixed_asset_categories.depreciation_expense_account_id' => ['table' => 'fixed_asset_categories', 'parent' => 'ledger_accounts', 'foreign_key' => 'depreciation_expense_account_id'],
        'fixed_asset_transactions.expense_item_id' => ['table' => 'fixed_asset_transactions', 'parent' => 'expense_items', 'foreign_key' => 'expense_item_id'],
        'fixed_asset_transactions.invoice_item_id' => ['table' => 'fixed_asset_transactions', 'parent' => 'invoice_items', 'foreign_key' => 'invoice_item_id'],
        'fixed_asset_transactions.journal_entry_id' => ['table' => 'fixed_asset_transactions', 'parent' => 'journal_entries', 'foreign_key' => 'journal_entry_id'],
        'fixed_asset_transactions.reversal_journal_entry_id' => ['table' => 'fixed_asset_transactions', 'parent' => 'journal_entries', 'foreign_key' => 'reversal_journal_entry_id'],
        'fixed_asset_depreciation_entries.journal_entry_id' => ['table' => 'fixed_asset_depreciation_entries', 'parent' => 'journal_entries', 'foreign_key' => 'journal_entry_id'],
        'fixed_asset_depreciation_entries.reversal_journal_entry_id' => ['table' => 'fixed_asset_depreciation_entries', 'parent' => 'journal_entries', 'foreign_key' => 'reversal_journal_entry_id'],
        'bank_reconciliation_allocations.bank_transaction_id' => ['table' => 'bank_reconciliation_allocations', 'parent' => 'bank_transactions', 'foreign_key' => 'bank_transaction_id'],
        'hmrc_vat_submissions.hmrc_vat_obligation_id' => ['table' => 'hmrc_vat_submissions', 'parent' => 'hmrc_vat_obligations', 'foreign_key' => 'hmrc_vat_obligation_id'],
    ];

    /** @var array<class-string, string> */
    private const RECONCILIATION_SOURCES = [
        Payment::class => 'payments',
        ExpensePayment::class => 'expense_payments',
        PurchaseCreditRefund::class => 'purchase_credit_refunds',
    ];

    /** @var array<class-string, string> */
    private const UNIVERSAL_CREDIT_SOURCES = [
        Payment::class => 'payments',
        ExpensePayment::class => 'expense_payments',
        PurchaseCreditRefund::class => 'purchase_credit_refunds',
        BankTransaction::class => 'bank_transactions',
        UniversalCreditManualEntry::class => 'universal_credit_manual_entries',
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

        $fixedAssetCategoryQuery = DB::table('fixed_asset_categories as category')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'category.business_id')
            ->where(fn ($query) => $query->whereNull('category.business_id')->orWhereNull('owner.id'));
        $report['fixed_asset_categories'] = [
            'invalid_count' => (clone $fixedAssetCategoryQuery)->count(),
            'sample_ids' => (clone $fixedAssetCategoryQuery)->limit(20)->pluck('category.id')->all(),
        ];

        $periodQuery = DB::table('accounting_periods as period')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'period.business_id')
            ->where(fn ($query) => $query->whereNull('period.business_id')->orWhereNull('owner.id'));
        $report['accounting_periods'] = [
            'invalid_count' => (clone $periodQuery)->count(),
            'sample_ids' => (clone $periodQuery)->limit(20)->pluck('period.id')->all(),
        ];

        $universalCreditProfileQuery = DB::table('universal_credit_profiles as profile')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'profile.business_id')
            ->where(fn ($query) => $query->whereNull('profile.business_id')->orWhereNull('owner.id'));
        $report['universal_credit_profiles'] = [
            'invalid_count' => (clone $universalCreditProfileQuery)->count(),
            'sample_ids' => (clone $universalCreditProfileQuery)->limit(20)->pluck('profile.id')->all(),
        ];

        $invalidUniversalCreditSnapshots = [];
        $universalCreditHasher = app(UniversalCreditPolicyHashService::class);

        DB::table('universal_credit_assessment_periods as period')
            ->leftJoin('universal_credit_policy_versions as policy', 'policy.id', '=', 'period.policy_version_id')
            ->select([
                'period.id', 'period.policy_rules_snapshot', 'period.policy_rules_hash',
                'policy.id as policy_id', 'policy.rules_hash as policy_rules_hash_source',
            ])
            ->orderBy('period.id')
            ->chunk(100, function ($periods) use (&$invalidUniversalCreditSnapshots, $universalCreditHasher): void {
                foreach ($periods as $period) {
                    $rules = is_string($period->policy_rules_snapshot)
                        ? json_decode($period->policy_rules_snapshot, true)
                        : $period->policy_rules_snapshot;

                    if (! is_array($rules)
                        || $period->policy_id === null
                        || ! hash_equals((string) $period->policy_rules_hash, $universalCreditHasher->hash($rules))
                        || ! hash_equals((string) $period->policy_rules_hash, (string) $period->policy_rules_hash_source)) {
                        $invalidUniversalCreditSnapshots[] = $period->id;
                    }
                }
            });

        $report['universal_credit_assessment_periods.policy_snapshot'] = [
            'invalid_count' => count($invalidUniversalCreditSnapshots),
            'sample_ids' => array_slice($invalidUniversalCreditSnapshots, 0, 20),
        ];

        $invalidUniversalCreditReviewSnapshots = [];
        $universalCreditCalculationHasher = app(UniversalCreditCalculationHashService::class);

        DB::table('universal_credit_assessment_periods')
            ->select([
                'id', 'status', 'review_snapshot_version', 'review_snapshot', 'review_snapshot_hash',
                'reviewed_by', 'reviewed_at', 'locked_by', 'locked_at',
            ])
            ->orderBy('id')
            ->chunk(100, function ($periods) use (&$invalidUniversalCreditReviewSnapshots, $universalCreditCalculationHasher): void {
                foreach ($periods as $period) {
                    $snapshot = is_string($period->review_snapshot)
                        ? json_decode($period->review_snapshot, true)
                        : $period->review_snapshot;
                    $requiresSnapshot = in_array($period->status, ['in_review', 'locked', 'reported'], true);
                    $hasSnapshot = is_array($snapshot)
                        && (int) $period->review_snapshot_version === 1
                        && preg_match('/^[a-f0-9]{64}$/', (string) $period->review_snapshot_hash) === 1;
                    $invalid = ($requiresSnapshot && ! $hasSnapshot)
                        || ($period->status === 'draft' && ($period->review_snapshot !== null || $period->review_snapshot_hash !== null));

                    if ($hasSnapshot
                        && ! hash_equals((string) $period->review_snapshot_hash, $universalCreditCalculationHasher->hash($snapshot))) {
                        $invalid = true;
                    }

                    if ($requiresSnapshot
                        && ($period->reviewed_by === null || $period->reviewed_at === null
                            || (in_array($period->status, ['locked', 'reported'], true)
                                && ($period->locked_by === null || $period->locked_at === null)))) {
                        $invalid = true;
                    }

                    if ($invalid) {
                        $invalidUniversalCreditReviewSnapshots[] = $period->id;
                    }
                }
            });

        $report['universal_credit_assessment_periods.review_snapshot'] = [
            'invalid_count' => count($invalidUniversalCreditReviewSnapshots),
            'sample_ids' => array_slice($invalidUniversalCreditReviewSnapshots, 0, 20),
        ];

        $invalidUniversalCreditSources = [];

        DB::table('universal_credit_report_items')
            ->select([
                'id', 'business_id', 'assessment_period_id', 'source_type', 'source_id',
                'source_event_key', 'source_identity_hash',
            ])
            ->orderBy('id')
            ->chunk(100, function ($items) use (&$invalidUniversalCreditSources): void {
                foreach ($items as $item) {
                    $sourceTable = self::UNIVERSAL_CREDIT_SOURCES[$item->source_type] ?? null;
                    $sourceBusinessId = $sourceTable === null
                        ? null
                        : DB::table($sourceTable)->where('id', $item->source_id)->value('business_id');
                    $expectedIdentity = UniversalCreditReportItem::identityHash(
                        (string) $item->business_id,
                        (int) $item->assessment_period_id,
                        (string) $item->source_type,
                        (int) $item->source_id,
                        (string) $item->source_event_key,
                    );

                    if ($sourceBusinessId === null
                        || $sourceBusinessId !== $item->business_id
                        || ! hash_equals((string) $item->source_identity_hash, $expectedIdentity)) {
                        $invalidUniversalCreditSources[] = $item->id;
                    }
                }
            });

        $report['universal_credit_report_items.source'] = [
            'invalid_count' => count($invalidUniversalCreditSources),
            'sample_ids' => array_slice($invalidUniversalCreditSources, 0, 20),
        ];

        $invalidUniversalCreditFxSnapshots = DB::table('universal_credit_report_items')
            ->where(function ($query): void {
                $query->where(function ($missingSnapshot): void {
                    $missingSnapshot->whereNotNull('gross_amount_pence')
                        ->where(function ($missingMetadata): void {
                            $missingMetadata->whereNull('gbp_exchange_rate')
                                ->orWhere('gbp_exchange_rate', '<=', 0)
                                ->orWhereNull('exchange_rate_source')
                                ->orWhere('exchange_rate_source', '')
                                ->orWhereNull('exchange_rate_date');
                        });
                })->orWhere(function ($orphanedMetadata): void {
                    $orphanedMetadata->whereNull('gross_amount_pence')
                        ->where(function ($metadata): void {
                            $metadata->whereNotNull('gbp_exchange_rate')
                                ->orWhereNotNull('exchange_rate_source')
                                ->orWhereNotNull('exchange_rate_date');
                        });
                });
            });
        $report['universal_credit_report_items.fx_snapshot'] = [
            'invalid_count' => (clone $invalidUniversalCreditFxSnapshots)->count(),
            'sample_ids' => (clone $invalidUniversalCreditFxSnapshots)->limit(20)->pluck('id')->all(),
        ];

        $invalidUniversalCreditSimplifiedExpenses = [];

        DB::table('universal_credit_simplified_expenses as expense')
            ->leftJoin('universal_credit_assessment_periods as period', 'period.id', '=', 'expense.assessment_period_id')
            ->leftJoin('universal_credit_policy_versions as policy', 'policy.id', '=', 'expense.policy_version_id')
            ->select([
                'expense.id', 'expense.policy_version_id', 'expense.policy_rules_hash',
                'period.policy_version_id as period_policy_version_id',
                'period.policy_rules_hash as period_policy_rules_hash',
                'policy.rules_hash as policy_rules_hash_source',
            ])
            ->orderBy('expense.id')
            ->chunk(100, function ($expenses) use (&$invalidUniversalCreditSimplifiedExpenses): void {
                foreach ($expenses as $expense) {
                    if ($expense->period_policy_version_id === null
                        || $expense->policy_rules_hash_source === null
                        || (int) $expense->policy_version_id !== (int) $expense->period_policy_version_id
                        || ! hash_equals((string) $expense->policy_rules_hash, (string) $expense->period_policy_rules_hash)
                        || ! hash_equals((string) $expense->policy_rules_hash, (string) $expense->policy_rules_hash_source)) {
                        $invalidUniversalCreditSimplifiedExpenses[] = $expense->id;
                    }
                }
            });

        $report['universal_credit_simplified_expenses.policy_snapshot'] = [
            'invalid_count' => count($invalidUniversalCreditSimplifiedExpenses),
            'sample_ids' => array_slice($invalidUniversalCreditSimplifiedExpenses, 0, 20),
        ];

        $vatReturnQuery = DB::table('vat_returns as vat_return')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'vat_return.business_id')
            ->where(fn ($query) => $query->whereNull('vat_return.business_id')->orWhereNull('owner.id'));
        $report['vat_returns'] = [
            'invalid_count' => (clone $vatReturnQuery)->count(),
            'sample_ids' => (clone $vatReturnQuery)->limit(20)->pluck('vat_return.id')->all(),
        ];

        $hmrcConnectionQuery = DB::table('hmrc_connections as connection')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'connection.business_id')
            ->where(fn ($query) => $query->whereNull('connection.business_id')->orWhereNull('owner.id'));
        $report['hmrc_connections'] = [
            'invalid_count' => (clone $hmrcConnectionQuery)->count(),
            'sample_ids' => (clone $hmrcConnectionQuery)->limit(20)->pluck('connection.id')->all(),
        ];

        $hmrcVatObligationQuery = DB::table('hmrc_vat_obligations as obligation')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'obligation.business_id')
            ->where(fn ($query) => $query->whereNull('obligation.business_id')->orWhereNull('owner.id'));
        $report['hmrc_vat_obligations'] = [
            'invalid_count' => (clone $hmrcVatObligationQuery)->count(),
            'sample_ids' => (clone $hmrcVatObligationQuery)->limit(20)->pluck('obligation.id')->all(),
        ];

        $hmrcSandboxEvidenceQuery = DB::table('hmrc_sandbox_evidence_events as evidence')
            ->leftJoin('businesses as owner', 'owner.id', '=', 'evidence.business_id')
            ->where(fn ($query) => $query->whereNull('evidence.business_id')->orWhereNull('owner.id'));
        $report['hmrc_sandbox_evidence_events'] = [
            'invalid_count' => (clone $hmrcSandboxEvidenceQuery)->count(),
            'sample_ids' => (clone $hmrcSandboxEvidenceQuery)->limit(20)->pluck('evidence.id')->all(),
        ];

        $invalidVatReturnSnapshots = [];
        $snapshotHasher = app(VatReturnSnapshotService::class);

        DB::table('vat_returns')
            ->select(['id', 'business_id', 'snapshot', 'snapshot_hash', 'source_journal_count', 'source_line_count'])
            ->orderBy('id')
            ->chunk(100, function ($returns) use (&$invalidVatReturnSnapshots, $snapshotHasher): void {
                foreach ($returns as $return) {
                    $snapshot = is_string($return->snapshot)
                        ? json_decode($return->snapshot, true)
                        : $return->snapshot;

                    if (! is_array($snapshot)) {
                        $invalidVatReturnSnapshots[] = $return->id;

                        continue;
                    }

                    $sourceRows = data_get($snapshot, 'sources.rows', []);
                    $journalIds = collect(is_array($sourceRows) ? $sourceRows : [])
                        ->pluck('journal_entry_id')->filter()->unique()->values();
                    $lineIds = collect(is_array($sourceRows) ? $sourceRows : [])
                        ->flatMap(fn (array $row): array => is_array($row['journal_line_ids'] ?? null) ? $row['journal_line_ids'] : [])
                        ->filter()->unique()->values();
                    $validJournalCount = $journalIds->isEmpty() ? 0 : DB::table('journal_entries')
                        ->where('business_id', $return->business_id)
                        ->whereIn('id', $journalIds)
                        ->count();
                    $validLineCount = $lineIds->isEmpty() ? 0 : DB::table('journal_lines')
                        ->where('business_id', $return->business_id)
                        ->whereIn('journal_entry_id', $journalIds)
                        ->whereIn('id', $lineIds)
                        ->count();

                    if ($validJournalCount !== $journalIds->count()
                        || $validLineCount !== $lineIds->count()
                        || (int) $return->source_journal_count !== $journalIds->count()
                        || (int) $return->source_line_count !== $lineIds->count()
                        || ! hash_equals((string) $return->snapshot_hash, $snapshotHasher->hash($snapshot))) {
                        $invalidVatReturnSnapshots[] = $return->id;
                    }
                }
            });

        $report['vat_returns.snapshot_sources'] = [
            'invalid_count' => count($invalidVatReturnSnapshots),
            'sample_ids' => array_slice($invalidVatReturnSnapshots, 0, 20),
        ];

        $reversalQuery = DB::table('journal_entries as reversal')
            ->leftJoin('journal_entries as original', 'original.id', '=', 'reversal.reversal_of_id')
            ->whereNotNull('reversal.reversal_of_id')
            ->where(function ($query): void {
                $query->whereNull('original.id')
                    ->orWhereColumn('reversal.business_id', '!=', 'original.business_id');
            });
        $report['journal_entries.reversal_of_id'] = [
            'invalid_count' => (clone $reversalQuery)->count(),
            'sample_ids' => (clone $reversalQuery)->limit(20)->pluck('reversal.id')->all(),
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

        $allocationTransactionQuery = DB::table('bank_reconciliation_allocations as allocation')
            ->leftJoin('bank_reconciliations as reconciliation', 'reconciliation.id', '=', 'allocation.bank_reconciliation_id')
            ->where(function ($query): void {
                $query->whereNull('reconciliation.id')
                    ->orWhereColumn('allocation.bank_transaction_id', '!=', 'reconciliation.bank_transaction_id');
            });
        $report['bank_reconciliation_allocations.batch_transaction'] = [
            'invalid_count' => (clone $allocationTransactionQuery)->count(),
            'sample_ids' => (clone $allocationTransactionQuery)->limit(20)->pluck('allocation.id')->all(),
        ];

        $reconciliationReversalQuery = DB::table('bank_reconciliations')
            ->where(function ($invalid): void {
                $invalid->whereNotIn('status', [
                    BankReconciliation::STATUS_ACTIVE,
                    BankReconciliation::STATUS_REVERSED,
                ])->orWhere(function ($reversed): void {
                    $reversed->where('status', BankReconciliation::STATUS_REVERSED)
                        ->where(function ($missingAudit): void {
                            $missingAudit->whereNull('reversed_by')
                                ->orWhereNull('reversed_at')
                                ->orWhereRaw("TRIM(COALESCE(reversal_reason, '')) = ''");
                        });
                })->orWhere(function ($active): void {
                    $active->where('status', BankReconciliation::STATUS_ACTIVE)
                        ->where(function ($unexpectedAudit): void {
                            $unexpectedAudit->whereNotNull('reversed_by')
                                ->orWhereNotNull('reversed_at')
                                ->orWhereNotNull('reversal_reason');
                        });
                });
            });
        $report['bank_reconciliations.reversal_audit'] = [
            'invalid_count' => (clone $reconciliationReversalQuery)->count(),
            'sample_ids' => (clone $reconciliationReversalQuery)->limit(20)->pluck('id')->all(),
        ];

        $sourceReport = ['invalid_count' => 0, 'sample_ids' => []];

        foreach (self::RECONCILIATION_SOURCES as $sourceType => $sourceTable) {
            $sourceQuery = DB::table('bank_reconciliation_allocations as allocation')
                ->leftJoin("{$sourceTable} as source", 'source.id', '=', 'allocation.reconcilable_id')
                ->where('allocation.reconcilable_type', $sourceType)
                ->where(function ($query): void {
                    $query->whereNull('source.id')
                        ->orWhereColumn('allocation.business_id', '!=', 'source.business_id');
                });
            $sourceReport['invalid_count'] += (clone $sourceQuery)->count();
            $sourceReport['sample_ids'] = array_slice(array_values(array_unique([
                ...$sourceReport['sample_ids'],
                ...(clone $sourceQuery)->limit(20)->pluck('allocation.id')->all(),
            ])), 0, 20);
        }

        $unknownSourceQuery = DB::table('bank_reconciliation_allocations')
            ->whereNotIn('reconcilable_type', array_keys(self::RECONCILIATION_SOURCES));
        $sourceReport['invalid_count'] += (clone $unknownSourceQuery)->count();
        $sourceReport['sample_ids'] = array_slice(array_values(array_unique([
            ...$sourceReport['sample_ids'],
            ...(clone $unknownSourceQuery)->limit(20)->pluck('id')->all(),
        ])), 0, 20);
        $report['bank_reconciliation_allocations.source'] = $sourceReport;

        $expensePaymentSettlementQuery = DB::table('expense_payments as payment')
            ->leftJoin('accounting_profiles as profile', 'profile.business_id', '=', 'payment.business_id')
            ->where(function ($invalid): void {
                $invalid->whereNotIn('payment.settlement_source', ExpensePayment::settlementSources())
                    ->orWhere(function ($bank): void {
                        $bank->where('payment.settlement_source', ExpensePayment::SOURCE_BANK_ACCOUNT)
                            ->whereNull('payment.bank_account_id');
                    })
                    ->orWhere(function ($nonBank): void {
                        $nonBank->whereIn('payment.settlement_source', [
                            ExpensePayment::SOURCE_CASH_ACCOUNT,
                            ExpensePayment::SOURCE_OWNER_FUNDS,
                        ])->whereNotNull('payment.bank_account_id');
                    })
                    ->orWhere(function ($owner): void {
                        $owner->where('payment.settlement_source', ExpensePayment::SOURCE_OWNER_FUNDS)
                            ->where(function ($profileMismatch): void {
                                $profileMismatch->whereNull('profile.id')
                                    ->orWhere('profile.entity_type', '!=', 'sole_trader');
                            });
                    });
            });
        $report['expense_payments.settlement_source'] = [
            'invalid_count' => (clone $expensePaymentSettlementQuery)->count(),
            'sample_ids' => (clone $expensePaymentSettlementQuery)->limit(20)->pluck('payment.id')->all(),
        ];

        $expenseReconciliationQuery = DB::table('bank_reconciliation_allocations as allocation')
            ->leftJoin('expense_payments as payment', 'payment.id', '=', 'allocation.reconcilable_id')
            ->leftJoin('bank_transactions as bank_transaction', 'bank_transaction.id', '=', 'allocation.bank_transaction_id')
            ->where('allocation.reconcilable_type', ExpensePayment::class)
            ->where(function ($invalid): void {
                $invalid->whereNull('payment.id')
                    ->orWhere('payment.settlement_source', '!=', ExpensePayment::SOURCE_BANK_ACCOUNT)
                    ->orWhereColumn('payment.bank_account_id', '!=', 'bank_transaction.bank_account_id');
            });
        $report['bank_reconciliation_allocations.expense_settlement'] = [
            'invalid_count' => (clone $expenseReconciliationQuery)->count(),
            'sample_ids' => (clone $expenseReconciliationQuery)->limit(20)->pluck('allocation.id')->all(),
        ];

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

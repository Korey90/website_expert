<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReverseJournalEntryAction
{
    public function __construct(private readonly PostJournalEntryAction $postJournal) {}

    public function execute(
        JournalEntry $entry,
        User $actor,
        string $reason,
        CarbonImmutable|string|null $reversalDate = null,
    ): JournalEntry {
        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new DomainException('A reversal reason between 1 and 2000 characters is required.');
        }

        $date = $reversalDate instanceof CarbonImmutable
            ? $reversalDate->toDateString()
            : ($reversalDate ?? today()->toDateString());

        return DB::transaction(function () use ($entry, $actor, $reason, $date): JournalEntry {
            Business::query()->lockForUpdate()->findOrFail($entry->business_id);
            $locked = JournalEntry::withoutGlobalScopes()->lockForUpdate()->findOrFail($entry->id);

            if ($locked->status === JournalEntry::STATUS_REVERSED) {
                $existing = JournalEntry::withoutGlobalScopes()
                    ->where('reversal_of_id', $locked->id)
                    ->first();

                if (! $existing) {
                    throw new DomainException('The reversed journal is missing its reversal entry.');
                }

                if ($locked->reversal_reason !== $reason) {
                    throw new DomainException('The journal has already been reversed for a different reason.');
                }

                return $existing->load('lines');
            }

            if ($locked->status !== JournalEntry::STATUS_POSTED) {
                throw new DomainException('Only a posted journal entry can be reversed.');
            }

            if ($locked->reversal_of_id !== null) {
                throw new DomainException('A reversal entry cannot itself be reversed.');
            }

            if ($date < $locked->entry_date->toDateString()) {
                throw new DomainException('A journal reversal cannot predate the original entry.');
            }

            $locked->load('lines');
            $reversal = $this->postJournal->execute(
                Business::query()->findOrFail($locked->business_id),
                $actor,
                [
                    'entry_date' => $date,
                    'description' => "Reversal of {$locked->entry_number}",
                    'idempotency_key' => "journal_reversal:{$locked->id}",
                    'source' => $locked,
                    'reversal_of' => $locked,
                    'source_snapshot' => [
                        'original_entry_id' => $locked->id,
                        'original_entry_number' => $locked->entry_number,
                        'original_entry_date' => $locked->entry_date->toDateString(),
                        'original_source_type' => $locked->source_type,
                        'original_source_id' => $locked->source_id,
                        'original_snapshot_hash' => $locked->source_snapshot_hash,
                        'reason' => $reason,
                    ],
                    'lines' => $locked->lines->map(fn ($line): array => [
                        'ledger_account_id' => $line->ledger_account_id,
                        'tax_code_id' => $line->tax_code_id,
                        'client_id' => $line->client_id,
                        'supplier_id' => $line->supplier_id,
                        'project_id' => $line->project_id,
                        'debit_gbp' => $line->credit_gbp,
                        'credit_gbp' => $line->debit_gbp,
                        'original_currency' => $line->original_currency,
                        'original_amount' => $line->original_amount,
                        'exchange_rate' => $line->exchange_rate,
                        'memo' => $line->memo,
                        'dimensions' => $line->dimensions,
                    ])->all(),
                ],
            );

            DB::table('journal_entries')->where('id', $locked->id)->update([
                'status' => JournalEntry::STATUS_REVERSED,
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_at' => now(),
            ]);

            return $reversal->load('lines');
        }, 3);
    }
}

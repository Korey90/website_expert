<?php

namespace App\Actions\Finance;

use App\Models\DocumentSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GenerateDocumentNumberAction
{
    /** @var array<string, string> */
    private const PREFIXES = [
        'invoice' => 'INV',
        'quote' => 'QUOT',
        'credit_note' => 'CN',
    ];

    /** @var array<string, string> */
    private const TABLES = [
        'invoice' => 'invoices',
        'quote' => 'quotes',
        'credit_note' => 'credit_notes',
    ];

    public function execute(
        string $businessId,
        string $documentType,
        ?CarbonInterface $date = null,
    ): string {
        $prefix = self::PREFIXES[$documentType] ?? throw new InvalidArgumentException(
            "Unsupported document type [{$documentType}]."
        );
        $year = (int) ($date ?? now())->format('Y');

        return DB::transaction(function () use ($businessId, $documentType, $prefix, $year): string {
            $startingNumber = $this->nextExistingNumber($businessId, $documentType, $prefix, $year);

            DB::table('document_sequences')->insertOrIgnore([
                'business_id' => $businessId,
                'document_type' => $documentType,
                'year' => $year,
                'next_number' => $startingNumber,
                'prefix' => $prefix,
                'padding' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DocumentSequence::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('document_type', $documentType)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $sequence->next_number;
            $sequence->update([
                'next_number' => $number + 1,
            ]);

            return sprintf(
                '%s-%d-%s',
                $sequence->prefix,
                $sequence->year,
                str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT),
            );
        }, 3);
    }

    private function nextExistingNumber(
        string $businessId,
        string $documentType,
        string $prefix,
        int $year,
    ): int {
        return DB::table(self::TABLES[$documentType])
            ->where('business_id', $businessId)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->pluck('number')
            ->reduce(function (int $next, string $number) use ($prefix, $year): int {
                if (! preg_match('/^'.preg_quote($prefix, '/').'-'.$year.'-(\d+)$/', $number, $matches)) {
                    return $next;
                }

                return max($next, ((int) $matches[1]) + 1);
            }, 1);
    }
}

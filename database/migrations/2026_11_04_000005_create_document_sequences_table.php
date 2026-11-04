<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('document_type', 32);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->string('prefix', 16);
            $table->unsignedTinyInteger('padding')->default(3);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'document_type', 'year'], 'document_sequences_tenant_type_year_unique');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['number']);
            $table->unique(['business_id', 'number']);
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropUnique(['number']);
            $table->unique(['business_id', 'number']);
        });

        $this->backfillSequence('invoices', 'invoice', 'INV');
        $this->backfillSequence('quotes', 'quote', 'QUOT');
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropUnique(['business_id', 'number']);
            $table->unique('number');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['business_id', 'number']);
            $table->unique('number');
        });

        Schema::dropIfExists('document_sequences');
    }

    private function backfillSequence(string $table, string $documentType, string $prefix): void
    {
        $sequences = [];

        DB::table($table)
            ->whereNotNull('business_id')
            ->select(['business_id', 'number'])
            ->orderBy('id')
            ->each(function (object $document) use (&$sequences, $prefix): void {
                if (! preg_match('/^'.preg_quote($prefix, '/').'-(\d{4})-(\d+)$/', $document->number, $matches)) {
                    return;
                }

                $key = $document->business_id.'|'.$matches[1];
                $sequences[$key] = [
                    'business_id' => $document->business_id,
                    'year' => (int) $matches[1],
                    'next_number' => max($sequences[$key]['next_number'] ?? 1, ((int) $matches[2]) + 1),
                ];
            });

        foreach ($sequences as $sequence) {
            DB::table('document_sequences')->insert([
                ...$sequence,
                'document_type' => $documentType,
                'prefix' => $prefix,
                'padding' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

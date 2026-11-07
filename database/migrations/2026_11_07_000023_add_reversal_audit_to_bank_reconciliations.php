<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table): void {
            $table->foreignId('reversed_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reconciled_at');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
            $table->index(
                ['business_id', 'status', 'reversed_at'],
                'bank_reconciliations_reversal_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table): void {
            $table->dropIndex('bank_reconciliations_reversal_index');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
    }
};

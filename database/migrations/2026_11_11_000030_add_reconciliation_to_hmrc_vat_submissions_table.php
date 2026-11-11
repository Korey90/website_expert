<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hmrc_vat_submissions', function (Blueprint $table): void {
            $table->string('reconciliation_status', 30)->nullable()->after('failure_status');
            $table->unsignedSmallInteger('reconciliation_count')->default(0)->after('reconciliation_status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_count');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->date('hmrc_received_date')->nullable()->after('reconciled_by');
            $table->string('reconciliation_correlation_id', 100)->nullable()->after('hmrc_received_date');
            $table->string('reconciliation_error_code', 100)->nullable()->after('reconciliation_correlation_id');
            $table->unsignedSmallInteger('reconciliation_error_status')->nullable()->after('reconciliation_error_code');
        });
    }

    public function down(): void
    {
        Schema::table('hmrc_vat_submissions', function (Blueprint $table): void {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn([
                'reconciliation_status', 'reconciliation_count', 'reconciled_at',
                'reconciled_by', 'hmrc_received_date', 'reconciliation_correlation_id',
                'reconciliation_error_code', 'reconciliation_error_status',
            ]);
        });
    }
};

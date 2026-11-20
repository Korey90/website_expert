<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_attachments', function (Blueprint $table): void {
            $table->unsignedSmallInteger('scan_attempts')->default(0)->after('scan_status');
            $table->string('scan_engine', 50)->nullable()->after('scan_attempts');
            $table->string('scan_error_code', 50)->nullable()->after('scan_engine');
            $table->timestamp('scan_requested_at')->nullable()->after('scan_error_code');
            $table->timestamp('scanned_at')->nullable()->after('scan_requested_at');
            $table->index(['scan_status', 'scan_requested_at'], 'financial_attachments_scan_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('financial_attachments', function (Blueprint $table): void {
            $table->dropIndex('financial_attachments_scan_queue_index');
            $table->dropColumn([
                'scan_attempts',
                'scan_engine',
                'scan_error_code',
                'scan_requested_at',
                'scanned_at',
            ]);
        });
    }
};

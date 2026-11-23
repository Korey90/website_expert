<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universal_credit_assessment_periods', function (Blueprint $table): void {
            $table->unsignedSmallInteger('review_snapshot_version')->nullable()->after('source_snapshot_hash');
            $table->json('review_snapshot')->nullable()->after('review_snapshot_version');
            $table->char('review_snapshot_hash', 64)->nullable()->after('review_snapshot');
            $table->unsignedInteger('preflight_warning_count')->default(0)->after('review_snapshot_hash');
            $table->string('review_confirmation_version', 64)->nullable()->after('preflight_warning_count');
        });
    }

    public function down(): void
    {
        Schema::table('universal_credit_assessment_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'review_snapshot_version', 'review_snapshot', 'review_snapshot_hash',
                'preflight_warning_count', 'review_confirmation_version',
            ]);
        });
    }
};

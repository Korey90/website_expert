<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->char('business_id', 26)
                ->nullable()
                ->after('id')
                ->index();

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->nullOnDelete();
        });

        // Backfill: assign all un-scoped clients to the first/only existing business
        // Safe for single-tenant migrations; multi-tenant imports need manual re-assignment
        DB::statement("
            UPDATE clients
            SET business_id = (SELECT id FROM businesses ORDER BY created_at LIMIT 1)
            WHERE business_id IS NULL
              AND EXISTS (SELECT 1 FROM businesses LIMIT 1)
        ");
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};

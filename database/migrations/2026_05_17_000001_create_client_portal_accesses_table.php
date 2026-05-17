<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['client_id', 'user_id']);
        });

        // Migrate existing data from clients.portal_user_id
        DB::statement("
            INSERT INTO client_portal_accesses (client_id, user_id, granted_at, created_at, updated_at)
            SELECT id, portal_user_id, created_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM clients
            WHERE portal_user_id IS NOT NULL
        ");

        // Drop old column
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['portal_user_id']);
            $table->dropColumn('portal_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('portal_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Restore first portal access per client
        DB::statement("
            UPDATE clients SET portal_user_id = (
                SELECT user_id FROM client_portal_accesses
                WHERE client_id = clients.id
                ORDER BY granted_at ASC
                LIMIT 1
            )
        ");

        Schema::dropIfExists('client_portal_accesses');
    }
};

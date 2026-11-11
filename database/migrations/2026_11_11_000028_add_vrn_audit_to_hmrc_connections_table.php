<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hmrc_connections', function (Blueprint $table): void {
            $table->foreignId('vrn_updated_by')
                ->nullable()
                ->after('vrn')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('vrn_updated_at')->nullable()->after('vrn_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('hmrc_connections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vrn_updated_by');
            $table->dropColumn('vrn_updated_at');
        });
    }
};

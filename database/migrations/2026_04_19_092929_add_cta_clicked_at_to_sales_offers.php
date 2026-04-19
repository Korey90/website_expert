<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_offers', function (Blueprint $table) {
            $table->timestamp('cta_clicked_at')->nullable()->after('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_offers', function (Blueprint $table) {
            $table->dropColumn('cta_clicked_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->decimal('budget_min', 12, 2)->nullable()->after('value');
            $table->decimal('budget_max', 12, 2)->nullable()->after('budget_min');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['budget_min', 'budget_max']);
        });
    }
};

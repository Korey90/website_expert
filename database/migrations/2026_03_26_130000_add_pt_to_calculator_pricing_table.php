<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            $table->string('label_pt', 255)->nullable()->after('label_pl');
            $table->text('desc_pt')->nullable()->after('desc_pl');
        });
    }

    public function down(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            $table->dropColumn(['label_pt', 'desc_pt']);
        });
    }
};

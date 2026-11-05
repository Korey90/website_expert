<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->unique('reversal_of_id', 'journal_reversal_of_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropUnique('journal_reversal_of_unique');
        });
    }
};

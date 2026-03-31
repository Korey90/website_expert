<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // SQLite does not enforce enums; existing TEXT column is sufficient
        }

        // MySQL / MariaDB: modify enum to add 'payu'
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('stripe','bank_transfer','cash','cheque','other','payu') NOT NULL DEFAULT 'stripe'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('stripe','bank_transfer','cash','cheque','other') NOT NULL DEFAULT 'stripe'");
    }
};

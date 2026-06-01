<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_price_list', function (Blueprint $table) {
            $table->decimal('wholesale_register', 10, 2)->nullable()->after('transfer_price');
            $table->decimal('wholesale_renew', 10, 2)->nullable()->after('wholesale_register');
            $table->decimal('wholesale_transfer', 10, 2)->nullable()->after('wholesale_renew');
            $table->decimal('margin_percent', 5, 2)->default(50.00)->after('wholesale_transfer');
        });
    }

    public function down(): void
    {
        Schema::table('domain_price_list', function (Blueprint $table) {
            $table->dropColumn(['wholesale_register', 'wholesale_renew', 'wholesale_transfer', 'margin_percent']);
        });
    }
};

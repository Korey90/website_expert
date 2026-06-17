<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domain_renewals') || Schema::hasColumn('domain_renewals', 'currency')) {
            return;
        }

        Schema::table('domain_renewals', function (Blueprint $table): void {
            $table->string('currency', 3)
                ->default(config('currencies.default', 'GBP'))
                ->after('retail_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('domain_renewals') || ! Schema::hasColumn('domain_renewals', 'currency')) {
            return;
        }

        Schema::table('domain_renewals', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};

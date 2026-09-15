<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable()->after('google_2fa_secret');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });

        // Carry over already-confirmed TOTP secrets to Filament's native multi-factor columns.
        DB::table('users')
            ->where('two_factor_enabled', true)
            ->whereNotNull('google_2fa_secret')
            ->update(['app_authentication_secret' => DB::raw('google_2fa_secret')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_2fa_secret', 'two_factor_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_2fa_secret')->nullable()->after('avatar_url');
            $table->boolean('two_factor_enabled')->default(false)->after('google_2fa_secret');
        });

        DB::table('users')
            ->whereNotNull('app_authentication_secret')
            ->update([
                'google_2fa_secret' => DB::raw('app_authentication_secret'),
                'two_factor_enabled' => true,
            ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('signature_data')->nullable()->after('signed_at'); // base64 canvas PNG
            $table->string('signer_ip', 45)->nullable()->after('signature_data');
            $table->string('signer_name')->nullable()->after('signer_ip');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['signature_data', 'signer_ip', 'signer_name']);
        });
    }
};

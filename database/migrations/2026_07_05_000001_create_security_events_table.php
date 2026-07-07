<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('jail', 64);
            $table->string('attack_type', 128)->nullable();
            $table->unsignedSmallInteger('failures')->default(0);
            $table->string('country', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('isp', 128)->nullable();
            $table->enum('action', ['banned', 'unbanned'])->default('banned')->index();
            $table->timestamp('banned_at')->nullable()->index();
            $table->timestamp('unbanned_at')->nullable();
            $table->timestamp('reported_to_abuseipdb_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};

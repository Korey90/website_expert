<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('business_id', 26)->nullable()->index();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at');

            // Which Google Calendar to sync with (default = primary)
            $table->string('calendar_id')->default('primary');

            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_tokens');
    }
};

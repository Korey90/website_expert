<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                           // CNT-2026-001
            $table->string('title');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['draft', 'sent', 'signed', 'expired', 'cancelled'])->default('draft');
            $table->string('currency', 3)->default('GBP');
            $table->decimal('value', 12, 2)->default(0);
            $table->longText('terms')->nullable();                        // HTML via TinyEditor
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();                     // signed PDF upload
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

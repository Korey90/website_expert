<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_attachments', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->nullable();
            $table->string('document_type', 40);
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('scan_status', 20)->default('pending');
            $table->date('retention_until');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(
                ['business_id', 'attachable_type', 'attachable_id', 'sha256'],
                'financial_attachments_owner_hash_unique',
            );
            $table->index(['business_id', 'document_type']);
            $table->index(['business_id', 'sha256']);
            $table->index(['business_id', 'retention_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_attachments');
    }
};

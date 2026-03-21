<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_stage_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('item_index');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'pipeline_stage_id', 'item_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_checklist_items');
    }
};

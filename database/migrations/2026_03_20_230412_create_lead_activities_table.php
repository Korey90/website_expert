<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'created',
                'stage_moved',
                'marked_won',
                'marked_lost',
                'email_sent',
                'note_updated',
                'project_created',
                'assigned',
                'deleted',
                'restored',
                'updated',
                'sms_sent',
                'other',
            ]);
            $table->string('description');
            $table->json('metadata')->nullable(); // e.g. {from_stage, to_stage, email_subject, …}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};

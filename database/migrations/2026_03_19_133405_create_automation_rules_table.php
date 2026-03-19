<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_event'); // lead.created, invoice.overdue, project.status_changed, etc.
            $table->json('conditions')->nullable(); // [{field, operator, value}]
            $table->json('actions'); // [{type: 'send_email', template_id, to: 'client|admin'}, {type: 'send_sms', ...}]
            $table->integer('delay_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};

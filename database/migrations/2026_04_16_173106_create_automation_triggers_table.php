<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();        // e.g. lead.created
            $table->string('label', 255);                // e.g. Lead Created
            $table->string('group', 100)->nullable();    // e.g. Leads, Projects
            $table->text('description')->nullable();
            $table->json('variables')->nullable();       // [{name, description}]
            $table->boolean('is_system')->default(false); // system triggers cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_triggers');
    }
};

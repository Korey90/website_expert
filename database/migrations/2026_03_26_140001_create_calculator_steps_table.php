<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_steps', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('step_number');
            $table->text('question_en');
            $table->text('question_pl')->nullable();
            $table->text('question_pt')->nullable();
            $table->text('hint_en')->nullable();
            $table->text('hint_pl')->nullable();
            $table->text('hint_pt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_steps');
    }
};

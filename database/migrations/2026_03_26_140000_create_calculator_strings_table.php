<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_strings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('group', 50)->default('misc')->index();
            $table->text('value_en');
            $table->text('value_pl')->nullable();
            $table->text('value_pt')->nullable();
            $table->string('note', 255)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_strings');
    }
};

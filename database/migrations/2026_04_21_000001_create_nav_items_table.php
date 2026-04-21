<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->json('label');                          // {"pl":"O nas","en":"About Us","pt":"Sobre Nós"}
            $table->string('href', 200);                   // "#about" | "/portfolio" | "https://..."
            $table->string('section_key', 100)->nullable();// "about" | null dla linków zewnętrznych
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};

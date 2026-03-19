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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Company details (UK market)
            $table->string('company_name');
            $table->string('trading_name')->nullable();
            $table->string('companies_house_number', 20)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['prospect', 'active', 'inactive', 'archived'])->default('prospect');
            $table->enum('source', ['website', 'referral', 'cold_outreach', 'social_media', 'google_ads', 'other'])->default('website');
            $table->string('industry')->nullable();
            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('country', 2)->default('GB');
            // Primary contact
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_email')->nullable();
            $table->string('primary_contact_phone')->nullable();
            // CRM
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('lifetime_value', 12, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->text('notes')->nullable();
            // Portal access
            $table->foreignId('portal_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 7 — Bundling
 * Adds an optional domain_order_id FK to invoices, projects, quotes, and
 * sales_offers so any document can be linked to a domain registration order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('domain_order_id')
                ->nullable()
                ->after('quote_id')
                ->constrained('domain_orders')
                ->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('domain_order_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('domain_orders')
                ->nullOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('domain_order_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('domain_orders')
                ->nullOnDelete();
        });

        Schema::table('sales_offers', function (Blueprint $table) {
            $table->foreignId('domain_order_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('domain_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_order_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_order_id');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_order_id');
        });

        Schema::table('sales_offers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_order_id');
        });
    }
};

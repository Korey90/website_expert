<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing plain-string values to JSON (en as default) BEFORE changing column types
        DB::table('pages')->get()->each(function ($page) {
            $updates = [];
            foreach (['title', 'content', 'meta_title', 'meta_description'] as $field) {
                $val = $page->$field;
                if ($val !== null && $val !== '' && ! str_starts_with(trim((string) $val), '{')) {
                    $updates[$field] = json_encode(['en' => $val, 'pl' => $val]);
                }
            }
            if (! empty($updates)) {
                DB::table('pages')->where('id', $page->id)->update($updates);
            }
        });

        // Widen columns so JSON translations fit
        Schema::table('pages', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('meta_title')->nullable()->change();
            $table->text('meta_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('meta_title')->nullable()->change();
            $table->string('meta_description')->nullable()->change();
        });
    }
};


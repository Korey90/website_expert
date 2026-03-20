<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add temporary JSON columns
        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('subject_json')->nullable()->after('subject');
            $table->json('body_html_json')->nullable()->after('body_html');
            $table->json('body_text_json')->nullable()->after('body_text');
        });

        // 2. Migrate existing string data → {"en": "...", "pl": "", "pt": ""}
        DB::table('email_templates')->get()->each(function ($row) {
            DB::table('email_templates')->where('id', $row->id)->update([
                'subject_json'   => json_encode(['en' => $row->subject   ?? '', 'pl' => '', 'pt' => '']),
                'body_html_json' => json_encode(['en' => $row->body_html ?? '', 'pl' => '', 'pt' => '']),
                'body_text_json' => json_encode(['en' => $row->body_text ?? '', 'pl' => '', 'pt' => '']),
            ]);
        });

        // 3. Drop old string/text columns
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['subject', 'body_html', 'body_text']);
        });

        // 4. Rename temporary columns to final names
        Schema::table('email_templates', function (Blueprint $table) {
            $table->renameColumn('subject_json',   'subject');
            $table->renameColumn('body_html_json', 'body_html');
            $table->renameColumn('body_text_json', 'body_text');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('subject_str')->nullable()->after('subject');
            $table->longText('body_html_str')->nullable()->after('body_html');
            $table->text('body_text_str')->nullable()->after('body_text');
        });

        DB::table('email_templates')->get()->each(function ($row) {
            $subject   = is_string($row->subject)   ? json_decode($row->subject,   true) : [];
            $body_html = is_string($row->body_html) ? json_decode($row->body_html, true) : [];
            $body_text = is_string($row->body_text) ? json_decode($row->body_text, true) : [];

            DB::table('email_templates')->where('id', $row->id)->update([
                'subject_str'   => $subject['en']   ?? '',
                'body_html_str' => $body_html['en'] ?? '',
                'body_text_str' => $body_text['en'] ?? null,
            ]);
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['subject', 'body_html', 'body_text']);
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->renameColumn('subject_str',   'subject');
            $table->renameColumn('body_html_str', 'body_html');
            $table->renameColumn('body_text_str', 'body_text');
        });
    }
};

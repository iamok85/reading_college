<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->longText('spelling_mistakes')->nullable()->after('ocr_text');
            $table->longText('grammar_mistakes')->nullable()->after('spelling_mistakes');
            $table->longText('corrected_version')->nullable()->after('grammar_mistakes');
        });
    }

    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->dropColumn(['spelling_mistakes', 'grammar_mistakes', 'corrected_version']);
        });
    }
};

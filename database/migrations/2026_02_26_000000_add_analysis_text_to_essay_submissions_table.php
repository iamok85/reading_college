<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->longText('analysis_text')->nullable()->after('corrected_version');
        });
    }

    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->dropColumn('analysis_text');
        });
    }
};

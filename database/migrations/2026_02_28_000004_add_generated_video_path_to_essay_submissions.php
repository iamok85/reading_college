<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('essay_submissions', 'generated_video_path')) {
                $table->string('generated_video_path')->nullable()->after('generated_image_paths');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('essay_submissions', 'generated_video_path')) {
                $table->dropColumn('generated_video_path');
            }
        });
    }
};

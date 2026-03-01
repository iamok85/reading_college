<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('essay_submissions', 'video_job_id')) {
                $table->string('video_job_id')->nullable()->after('generated_video_path');
            }
            if (!Schema::hasColumn('essay_submissions', 'video_status')) {
                $table->string('video_status')->nullable()->after('video_job_id');
            }
            if (!Schema::hasColumn('essay_submissions', 'video_progress')) {
                $table->unsignedSmallInteger('video_progress')->nullable()->after('video_status');
            }
            if (!Schema::hasColumn('essay_submissions', 'video_error')) {
                $table->text('video_error')->nullable()->after('video_progress');
            }
            if (!Schema::hasColumn('essay_submissions', 'video_url')) {
                $table->text('video_url')->nullable()->after('video_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('essay_submissions', 'video_url')) {
                $table->dropColumn('video_url');
            }
            if (Schema::hasColumn('essay_submissions', 'video_error')) {
                $table->dropColumn('video_error');
            }
            if (Schema::hasColumn('essay_submissions', 'video_progress')) {
                $table->dropColumn('video_progress');
            }
            if (Schema::hasColumn('essay_submissions', 'video_status')) {
                $table->dropColumn('video_status');
            }
            if (Schema::hasColumn('essay_submissions', 'video_job_id')) {
                $table->dropColumn('video_job_id');
            }
        });
    }
};

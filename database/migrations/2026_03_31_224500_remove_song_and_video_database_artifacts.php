<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('essay_songs');
        Schema::dropIfExists('suno_logs');

        if (Schema::hasTable('essay_submissions')) {
            Schema::table('essay_submissions', function (Blueprint $table): void {
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
                if (Schema::hasColumn('essay_submissions', 'generated_video_path')) {
                    $table->dropColumn('generated_video_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('essay_submissions')) {
            Schema::table('essay_submissions', function (Blueprint $table): void {
                if (! Schema::hasColumn('essay_submissions', 'generated_video_path')) {
                    $table->string('generated_video_path')->nullable()->after('generated_image_paths');
                }
                if (! Schema::hasColumn('essay_submissions', 'video_job_id')) {
                    $table->string('video_job_id')->nullable()->after('generated_video_path');
                }
                if (! Schema::hasColumn('essay_submissions', 'video_status')) {
                    $table->string('video_status')->nullable()->after('video_job_id');
                }
                if (! Schema::hasColumn('essay_submissions', 'video_progress')) {
                    $table->unsignedSmallInteger('video_progress')->nullable()->after('video_status');
                }
                if (! Schema::hasColumn('essay_submissions', 'video_error')) {
                    $table->text('video_error')->nullable()->after('video_progress');
                }
                if (! Schema::hasColumn('essay_submissions', 'video_url')) {
                    $table->text('video_url')->nullable()->after('video_error');
                }
            });
        }

        if (! Schema::hasTable('essay_songs')) {
            Schema::create('essay_songs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('essay_submission_id')->constrained('essay_submissions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
                $table->string('status', 20)->default('pending');
                $table->string('song_name')->nullable();
                $table->string('song_path')->nullable();
                $table->string('provider', 30)->default('suno');
                $table->string('provider_song_id')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('suno_logs')) {
            Schema::create('suno_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('context')->nullable();
                $table->string('model')->nullable();
                $table->longText('prompt')->nullable();
                $table->longText('response')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }
};

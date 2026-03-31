<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('essay_submissions', 'processing_status')) {
                $table->string('processing_status')->default('completed')->after('video_url');
            }
            if (!Schema::hasColumn('essay_submissions', 'processing_error')) {
                $table->text('processing_error')->nullable()->after('processing_status');
            }
            if (!Schema::hasColumn('essay_submissions', 'processing_completed_at')) {
                $table->timestamp('processing_completed_at')->nullable()->after('processing_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('essay_submissions', 'processing_completed_at')) {
                $table->dropColumn('processing_completed_at');
            }
            if (Schema::hasColumn('essay_submissions', 'processing_error')) {
                $table->dropColumn('processing_error');
            }
            if (Schema::hasColumn('essay_submissions', 'processing_status')) {
                $table->dropColumn('processing_status');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_recommendations', function (Blueprint $table) {
            if (!Schema::hasColumn('reading_recommendations', 'processing_status')) {
                $table->string('processing_status')->default('completed')->after('items');
            }

            if (!Schema::hasColumn('reading_recommendations', 'processing_error')) {
                $table->text('processing_error')->nullable()->after('processing_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reading_recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('reading_recommendations', 'processing_error')) {
                $table->dropColumn('processing_error');
            }

            if (Schema::hasColumn('reading_recommendations', 'processing_status')) {
                $table->dropColumn('processing_status');
            }
        });
    }
};

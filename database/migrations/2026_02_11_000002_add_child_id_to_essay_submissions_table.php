<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->foreignId('child_id')->nullable()->after('user_id')->constrained('children')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('essay_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });
    }
};

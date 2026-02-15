<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('essay_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->unsignedInteger('essay_count')->default(0);
            $table->timestamp('last_submission_at')->nullable();
            $table->longText('analysis_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_analyses');
    }
};

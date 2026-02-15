<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('essay_songs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_essays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('essay_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained()->nullOnDelete();
            $table->string('child_name')->nullable();
            $table->unsignedTinyInteger('child_age')->nullable();
            $table->longText('corrected_text')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();

            $table->unique('essay_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_essays');
    }
};

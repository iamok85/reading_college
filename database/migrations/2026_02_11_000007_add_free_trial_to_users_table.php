<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('free_trial_used_at')->nullable()->after('plan_type');
            $table->timestamp('free_trial_ends_at')->nullable()->after('free_trial_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['free_trial_used_at', 'free_trial_ends_at']);
        });
    }
};

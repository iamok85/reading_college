<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'credits')) {
                $table->unsignedInteger('credits')->default(100)->after('free_trial_ends_at');
            }
        });

        DB::table('users')
            ->whereNull('credits')
            ->update(['credits' => 100]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'credits')) {
                $table->dropColumn('credits');
            }
        });
    }
};

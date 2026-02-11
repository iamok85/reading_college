<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'child_birth_year')) {
                $table->unsignedSmallInteger('child_birth_year')->nullable()->after('child_name');
            }
        });

        $year = now()->year;
        if (Schema::hasColumn('users', 'child_age')) {
            DB::table('users')
                ->whereNull('child_birth_year')
                ->whereNotNull('child_age')
                ->update([
                    'child_birth_year' => DB::raw($year . ' - child_age'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'child_birth_year')) {
                $table->dropColumn('child_birth_year');
            }
        });
    }
};

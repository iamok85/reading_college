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
        Schema::table('children', function (Blueprint $table) {
            if (!Schema::hasColumn('children', 'birth_year')) {
                $table->unsignedSmallInteger('birth_year')->nullable()->after('name');
            }
        });

        $year = now()->year;
        if (Schema::hasColumn('children', 'age')) {
            DB::table('children')
                ->whereNull('birth_year')
                ->whereNotNull('age')
                ->update([
                    'birth_year' => DB::raw($year . ' - age'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            if (Schema::hasColumn('children', 'birth_year')) {
                $table->dropColumn('birth_year');
            }
        });
    }
};

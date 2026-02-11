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
        Schema::table('users', function (Blueprint $table) {
            $table->string('child_name')->nullable()->after('name');
            $table->unsignedTinyInteger('child_age')->nullable()->after('child_name');
            $table->string('child_gender')->nullable()->after('child_age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['child_name', 'child_age', 'child_gender']);
        });
    }
};

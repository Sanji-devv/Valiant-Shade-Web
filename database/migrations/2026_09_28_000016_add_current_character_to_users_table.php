<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'current_character')) {
                $table->string('current_character')->nullable();
            }
            if (!Schema::hasColumn('users', 'active_main_character_id')) {
                $table->unsignedBigInteger('active_main_character_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'active_clerk_character_id')) {
                $table->unsignedBigInteger('active_clerk_character_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(
                ['current_character', 'active_main_character_id', 'active_clerk_character_id'],
                fn($col) => Schema::hasColumn('users', $col)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
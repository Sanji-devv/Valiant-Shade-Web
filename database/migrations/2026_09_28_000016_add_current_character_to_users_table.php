<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'current_character')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('current_character')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'current_character')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('current_character');
            });
        }
    }
};
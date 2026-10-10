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
        Schema::table('character_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('character_profiles', 'character_id')) {
                $table->string('character_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('character_profiles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('character_id');
            }
            if (!Schema::hasColumn('character_profiles', 'sheet_data')) {
                $table->json('sheet_data')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_profiles', function (Blueprint $table) {
            $cols = array_filter(['character_id', 'user_id', 'sheet_data'], fn($c) => Schema::hasColumn('character_profiles', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

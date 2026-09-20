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
            $table->string('character_id')->nullable()->index()->after('id');
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('character_id');
            $table->json('sheet_data')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_profiles', function (Blueprint $table) {
            $table->dropIndex(['character_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['character_id', 'user_id', 'sheet_data']);
        });
    }
};

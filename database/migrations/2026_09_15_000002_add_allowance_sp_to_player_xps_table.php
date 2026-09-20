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
        Schema::table('player_xps', function (Blueprint $table) {
            $table->unsignedBigInteger('allowance_sp')->default(0)->after('inactive_threshold_xp');
            $table->index('allowance_sp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_xps', function (Blueprint $table) {
            $table->dropIndex(['allowance_sp']);
            $table->dropColumn('allowance_sp');
        });
    }
};

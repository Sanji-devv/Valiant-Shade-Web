<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('territory_maps', function (Blueprint $table) {
            $table->json('portals')->nullable()->after('terrain_path');
        });
    }

    public function down(): void
    {
        Schema::table('territory_maps', function (Blueprint $table) {
            $table->dropColumn('portals');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_items', 'display_name')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('display_name')->nullable();
            });
        }

        if (! Schema::hasColumn('inventory_items', 'slug')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('slug')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_items', 'slug')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasColumn('inventory_items', 'display_name')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('display_name');
            });
        }
    }
};

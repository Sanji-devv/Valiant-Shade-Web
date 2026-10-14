<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Superseded by 2026_10_14_101500_create_activity_log_table (must exist before permission seed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_log')) {
            return;
        }
    }

    public function down(): void
    {
        //
    }
};

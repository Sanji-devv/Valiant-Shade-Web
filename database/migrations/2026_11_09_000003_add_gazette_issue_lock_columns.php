<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        Schema::table('gazette_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('gazette_issues', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_current');
            }
            if (! Schema::hasColumn('gazette_issues', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            }
            if (! Schema::hasColumn('gazette_issues', 'archive_snapshot')) {
                $table->json('archive_snapshot')->nullable()->after('locked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gazette_issues', function (Blueprint $table) {
            $columns = ['archive_snapshot', 'locked_at', 'is_locked'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('gazette_issues', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

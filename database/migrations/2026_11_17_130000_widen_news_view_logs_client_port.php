<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_view_logs') || ! Schema::hasColumn('news_view_logs', 'client_port')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_view_logs ALTER COLUMN client_port TYPE INTEGER USING client_port::integer');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE news_view_logs MODIFY client_port INT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('news_view_logs') || ! Schema::hasColumn('news_view_logs', 'client_port')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_view_logs ALTER COLUMN client_port TYPE SMALLINT USING client_port::smallint');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE news_view_logs MODIFY client_port SMALLINT UNSIGNED NULL');
        }
    }
};
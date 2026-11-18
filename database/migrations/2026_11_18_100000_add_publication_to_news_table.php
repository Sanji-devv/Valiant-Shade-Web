<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'publication')) {
                $table->string('publication', 32)->default('lagimdan_sesler')->after('news_type');
                $table->index(['publication', 'status', 'is_active']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('news') || ! Schema::hasColumn('news', 'publication')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['publication', 'status', 'is_active']);
            $table->dropColumn('publication');
        });
    }
};
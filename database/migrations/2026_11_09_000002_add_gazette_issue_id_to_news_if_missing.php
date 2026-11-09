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

        if (Schema::hasColumn('news', 'gazette_issue_id')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('gazette_issue_id')->nullable()->after('news_type')->constrained('gazette_issues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('news', 'gazette_issue_id')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gazette_issue_id');
        });
    }
};

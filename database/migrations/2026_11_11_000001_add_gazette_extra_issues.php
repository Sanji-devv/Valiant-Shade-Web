<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        try {
            Schema::table('gazette_issues', function (Blueprint $table) {
                $table->dropUnique(['issue_number']);
            });
        } catch (\Throwable) {
            // Zaten kaldırılmış olabilir.
        }

        Schema::table('gazette_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('gazette_issues', 'issue_kind')) {
                $table->string('issue_kind', 16)->default('weekly')->after('id');
            }
            if (! Schema::hasColumn('gazette_issues', 'parent_issue_id')) {
                $table->foreignId('parent_issue_id')->nullable()->after('issue_kind')->constrained('gazette_issues')->nullOnDelete();
            }
            if (! Schema::hasColumn('gazette_issues', 'extra_sequence')) {
                $table->unsignedTinyInteger('extra_sequence')->nullable()->after('issue_number');
            }
        });

        DB::table('gazette_issues')->whereNull('issue_kind')->update(['issue_kind' => 'weekly']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        Schema::table('gazette_issues', function (Blueprint $table) {
            if (Schema::hasColumn('gazette_issues', 'parent_issue_id')) {
                $table->dropConstrainedForeignId('parent_issue_id');
            }
            if (Schema::hasColumn('gazette_issues', 'extra_sequence')) {
                $table->dropColumn('extra_sequence');
            }
            if (Schema::hasColumn('gazette_issues', 'issue_kind')) {
                $table->dropColumn('issue_kind');
            }
        });
    }
};

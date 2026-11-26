<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_narrative_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('quest_report_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quest_narrative_reports', function (Blueprint $table) {
            $table->dropIndex(['quest_report_id']);
            $table->dropColumn('quest_report_id');
        });
    }
};

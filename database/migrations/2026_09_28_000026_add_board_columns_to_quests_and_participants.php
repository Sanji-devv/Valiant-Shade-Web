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
        Schema::table('quests', function (Blueprint $table) {
            if (!Schema::hasColumn('quests', 'board_status')) {
                $table->string('board_status')->default('RECRUITING')->after('status');
            }
            if (!Schema::hasColumn('quests', 'min_level')) {
                $table->integer('min_level')->default(1)->after('board_status');
            }
            if (!Schema::hasColumn('quests', 'max_level')) {
                $table->integer('max_level')->default(20)->after('min_level');
            }
            if (!Schema::hasColumn('quests', 'max_party_size')) {
                $table->integer('max_party_size')->default(5)->after('max_level');
            }
            if (!Schema::hasColumn('quests', 'application_deadline')) {
                $table->timestamp('application_deadline')->nullable()->after('max_party_size');
            }
        });

        Schema::table('quest_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('quest_participants', 'attendance_status')) {
                $table->string('attendance_status')->default('COMPLETED')->after('character_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn(['board_status', 'min_level', 'max_level', 'max_party_size', 'application_deadline']);
        });

        Schema::table('quest_participants', function (Blueprint $table) {
            $table->dropColumn(['attendance_status']);
        });
    }
};

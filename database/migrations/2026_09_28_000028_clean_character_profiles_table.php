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
        Schema::table('character_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('character_profiles', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('player_name');
            }
            if (!Schema::hasColumn('character_profiles', 'title')) {
                $table->string('title')->nullable()->after('avatar_url');
            }
            if (!Schema::hasColumn('character_profiles', 'bio_text')) {
                $table->text('bio_text')->nullable()->after('title');
            }
            if (!Schema::hasColumn('character_profiles', 'rp_notes')) {
                $table->text('rp_notes')->nullable()->after('bio_text');
            }

            // Drop duplicate combat/stat columns, preserving character_info as Single Source of Truth
            $combatColumns = ['hp', 'max_hp', 'hit_die', 'bab', 'fort_save', 'ref_save', 'will_save', 'str', 'dex', 'con', 'int', 'wis', 'cha'];
            $toDrop = array_filter($combatColumns, fn($col) => Schema::hasColumn('character_profiles', $col));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_profiles', function (Blueprint $table) {
            $table->dropColumn(['avatar_url', 'title', 'bio_text', 'rp_notes']);
            $table->integer('str')->default(10);
            $table->integer('dex')->default(10);
            $table->integer('con')->default(10);
            $table->integer('int')->default(10);
            $table->integer('wis')->default(10);
            $table->integer('cha')->default(10);
            $table->integer('hp')->nullable();
            $table->integer('max_hp')->nullable();
            $table->string('hit_die')->default('d10');
            $table->string('bab')->nullable();
            $table->string('fort_save')->nullable();
            $table->string('ref_save')->nullable();
            $table->string('will_save')->nullable();
        });
    }
};

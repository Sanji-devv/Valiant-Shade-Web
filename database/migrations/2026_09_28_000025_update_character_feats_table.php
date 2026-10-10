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
        Schema::table('character_feats', function (Blueprint $table) {
            if (!Schema::hasColumn('character_feats', 'character_id')) {
                $table->foreignId('character_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('characters')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('character_feats', 'feat_id')) {
                $table->foreignId('feat_id')
                    ->nullable()
                    ->after('character_id')
                    ->constrained('feats')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('character_feats', 'character_name')) {
                $table->string('character_name')->nullable()->index();
            }

            if (!Schema::hasColumn('character_feats', 'feat_name')) {
                $table->string('feat_name')->nullable();
            }

            if (!Schema::hasColumn('character_feats', 'custom_feat_name')) {
                $table->string('custom_feat_name')->nullable();
            }

            if (!Schema::hasColumn('character_feats', 'class_feat_name')) {
                $table->string('class_feat_name')->nullable();
            }

            if (!Schema::hasColumn('character_feats', 'category')) {
                $table->string('category')->nullable();
            }

            if (!Schema::hasColumn('character_feats', 'prerequisites')) {
                $table->text('prerequisites')->nullable();
            }

            if (!Schema::hasColumn('character_feats', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }

            if (!Schema::hasColumn('character_feats', 'acquired_at_level')) {
                $table->integer('acquired_at_level')->default(1);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_feats', function (Blueprint $table) {
            if (Schema::hasColumn('character_feats', 'feat_id')) {
                $table->dropForeign(['feat_id']);
                $table->dropColumn('feat_id');
            }
            if (Schema::hasColumn('character_feats', 'character_id')) {
                $table->dropForeign(['character_id']);
                $table->dropColumn('character_id');
            }
            if (Schema::hasColumn('character_feats', 'custom_feat_name')) {
                $table->dropColumn('custom_feat_name');
            }
            if (Schema::hasColumn('character_feats', 'acquired_at_level')) {
                $table->dropColumn('acquired_at_level');
            }
        });
    }
};

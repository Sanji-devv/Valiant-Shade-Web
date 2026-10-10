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
        if (Schema::hasTable('character_traits')) {
            Schema::table('character_traits', function (Blueprint $table) {
                if (!Schema::hasColumn('character_traits', 'character_name')) {
                    $table->string('character_name')->nullable()->index();
                }

                if (!Schema::hasColumn('character_traits', 'slot_key')) {
                    $table->string('slot_key')->nullable()->index();
                }

                if (!Schema::hasColumn('character_traits', 'slot_name')) {
                    $table->string('slot_name')->nullable();
                }

                if (!Schema::hasColumn('character_traits', 'required_rank')) {
                    $table->string('required_rank')->nullable();
                }

                if (!Schema::hasColumn('character_traits', 'required_hierarchy')) {
                    $table->integer('required_hierarchy')->default(0);
                }

                if (!Schema::hasColumn('character_traits', 'trait_name')) {
                    $table->string('trait_name')->nullable();
                }

                if (!Schema::hasColumn('character_traits', 'category')) {
                    $table->string('category')->nullable();
                }

                if (!Schema::hasColumn('character_traits', 'description')) {
                    $table->text('description')->nullable();
                }

                if (!Schema::hasColumn('character_traits', 'source_url')) {
                    $table->text('source_url')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('character_traits')) {
            Schema::table('character_traits', function (Blueprint $table) {
                $columns = ['character_name', 'slot_key', 'slot_name', 'required_rank', 'required_hierarchy', 'trait_name', 'category', 'description', 'source_url'];
                $existing = array_filter($columns, fn($col) => Schema::hasColumn('character_traits', $col));
                if (!empty($existing)) {
                    $table->dropColumn($existing);
                }
            });
        }
    }
};

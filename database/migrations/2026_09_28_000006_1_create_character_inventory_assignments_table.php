<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zimmet: lonca/parti envanterinden karaktere çıkan eşya kayıtları.
     * Aktif zimmet: custody_status = ASSIGNED ve returned_at IS NULL.
     */
    public function up(): void
    {
        Schema::create('character_inventory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('custody_status', [
                'ASSIGNED',
                'RETURNED',
                'LOST',
                'DESTROYED',
                'CONSUMED',
            ])->default('ASSIGNED');

            $table->string('release_reason')->nullable();
            $table->foreignId('release_quest_id')->nullable()->constrained('quests')->nullOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['character_id', 'custody_status']);
            $table->index(['inventory_item_id', 'custody_status']);
            $table->index('returned_at');
        });

        // Her fiziksel inventory_items satırı aynı anda tek aktif zimmette olabilir.
        DB::statement("
            CREATE UNIQUE INDEX character_inventory_assignments_one_active_holder
            ON character_inventory_assignments (inventory_item_id)
            WHERE custody_status = 'ASSIGNED' AND returned_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS character_inventory_assignments_one_active_holder');
        Schema::dropIfExists('character_inventory_assignments');
    }
};

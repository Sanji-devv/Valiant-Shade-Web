<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('quest_date')->nullable();
            $table->boolean('is_main_quest')->default(false);
            $table->integer('level')->default(1);
            $table->decimal('base_xp', 12, 2)->default(0.00);

            $table->decimal('depo_fee_xp', 12, 2)->default(0.00);
            $table->decimal('guild_cut_xp', 12, 2)->default(0.00);
            $table->decimal('report_reward_xp', 12, 2)->default(0.00);
            $table->decimal('bonus_pool_xp', 12, 2)->default(0.00);
            $table->decimal('net_pool_xp', 12, 2)->default(0.00);
            $table->decimal('per_player_xp', 12, 2)->default(0.00);

            $table->foreignId('gm_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->foreignId('reporter_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED'])->default('DRAFT');
            $table->text('report_summary')->nullable();
            $table->text('full_report')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
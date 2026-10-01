<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->unique()->constrained('characters')->cascadeOnDelete();

            $table->string('class_and_level');
            $table->string('race');
            $table->enum('alignment', ['LG', 'NG', 'CG', 'LN', 'TN', 'CN', 'LE', 'NE', 'CE']);
            $table->string('deity')->nullable();
            $table->string('size')->default('MEDIUM');

            $table->integer('strength')->default(10);
            $table->integer('dexterity')->default(10);
            $table->integer('constitution')->default(10);
            $table->integer('intelligence')->default(10);
            $table->integer('wisdom')->default(10);
            $table->integer('charisma')->default(10);

            $table->integer('hp_max')->default(10);
            $table->integer('armor_class_total')->default(10);
            $table->integer('touch_ac')->default(10);
            $table->integer('flat_footed_ac')->default(10);
            $table->jsonb('ac_breakdown')->nullable();
            $table->string('damage_reduction')->nullable();
            $table->integer('spell_resistance')->default(0);

            $table->integer('fortitude_save')->default(0);
            $table->integer('reflex_save')->default(0);
            $table->integer('will_save')->default(0);
            $table->integer('base_attack_bonus')->default(0);
            $table->integer('cmb')->default(0);
            $table->integer('cmd')->default(10);
            $table->integer('initiative_mod')->default(0);
            $table->integer('speed_ft')->default(30);

            $table->jsonb('melee_attacks')->nullable();
            $table->jsonb('ranged_attacks')->nullable();
            $table->text('special_attacks')->nullable();
            $table->jsonb('equipped_gear')->nullable();
            $table->jsonb('inventory_items')->nullable();
            $table->decimal('total_carried_weight_lbs', 8, 2)->default(0.00);
            $table->string('load_capacity')->default('LIGHT');
            $table->jsonb('pocket_money')->nullable();
            $table->jsonb('currency')->nullable();

            $table->jsonb('skills')->nullable();
            $table->jsonb('feats_and_traits')->nullable();
            $table->jsonb('spellbook_or_prepared')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_info');
    }
};
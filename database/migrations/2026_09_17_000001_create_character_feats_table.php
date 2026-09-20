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
        Schema::create('character_feats', function (Blueprint $table) {
            $table->id();
            $table->string('character_name')->index();
            $table->integer('level')->default(1);
            // standard (1,3,5..), racial_bonus (e.g. human), class_bonus (fighter/monk), class_talent (rogue/magus/alchemist), trait, reserve
            $table->string('slot_type')->default('standard');
            $table->string('feat_name');
            $table->string('class_feat_name')->nullable();
            $table->string('category')->nullable(); // Combat, General, Metamagic, Rogue Talent, Magus Arcana, Discovery, Hex, Style, Teamwork, etc.
            $table->string('prerequisites')->nullable(); // e.g. Preq(Spell Focus(evocation),-)
            $table->text('notes')->nullable();
            $table->boolean('is_class_feat')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['character_name', 'level']);
            $table->index('slot_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('character_feats')) {
            Schema::create('character_feats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
                $table->foreignId('feat_id')->nullable()->constrained('feats')->nullOnDelete();
                $table->string('character_name')->nullable()->index();
                $table->string('feat_name')->nullable();
                $table->string('custom_feat_name')->nullable();
                $table->string('class_feat_name')->nullable();
                $table->string('category')->nullable();
                $table->text('prerequisites')->nullable();
                // standard (1,3,5..), racial_bonus (e.g. human), class_bonus (fighter/monk), class_talent (rogue/magus/alchemist), trait, reserve
                $table->string('slot_type')->default('standard');
                $table->text('notes')->nullable();
                $table->boolean('is_class_feat')->default(false);

                $table->integer('level')->default(1);
                $table->integer('acquired_at_level')->default(1);
                $table->integer('sort_order')->default(0);

                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};

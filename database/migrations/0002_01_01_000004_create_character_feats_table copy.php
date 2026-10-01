<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_feats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('feat_id')->constrained('feats')->cascadeOnDelete();
            // standard (1,3,5..), racial_bonus (e.g. human), class_bonus (fighter/monk), class_talent (rogue/magus/alchemist), trait, reserve
            $table->string('slot_type')->default('standard');
            $table->text('notes')->nullable();
            $table->boolean('is_class_feat')->default(false);

            /**
             * @todo bu level dağıtımı enuma taşınacak hangi levelsa id ile feat eşleşecek
             */
            $table->integer('level')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};

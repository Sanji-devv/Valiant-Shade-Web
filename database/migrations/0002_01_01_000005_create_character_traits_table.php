<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('trait_id')->constrained('traits')->cascadeOnDelete();
            $table->text('notes')->nullable();

            /**
             * @todo bu rank dağıtımı enuma taşınacak hangi ranksa id ile trait eşleşecek
             */
            $table->integer('rank')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};

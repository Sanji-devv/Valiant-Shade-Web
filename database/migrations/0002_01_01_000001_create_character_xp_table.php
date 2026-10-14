<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_xp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();

            $table->integer('current_level')->default(1);
            $table->decimal('total_xp', 12, 2)->default(0.00);
            $table->decimal('quest_xp', 12, 2)->default(0.00);
            $table->decimal('report_xp', 12, 2)->default(0.00);
            $table->decimal('bailiff_xp', 12, 2)->default(0.00);
            $table->decimal('ex_char_xp', 12, 2)->default(0.00);
            $table->decimal('wiki_xp', 12, 2)->default(0.00);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_xp');
    }
};

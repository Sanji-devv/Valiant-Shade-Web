<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters_xp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id')->nullable()->index();
            $table->string('character_name')->nullable()->index();
            $table->string('player_name')->nullable()->index();

            $table->decimal('total_xp', 12, 2)->default(0.00);
            $table->decimal('ex_char_xp', 12, 2)->default(0.00);
            $table->decimal('ex_bailiff_xp', 12, 2)->default(0.00);
            $table->decimal('wiki_xp', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters_xp');
    }
};
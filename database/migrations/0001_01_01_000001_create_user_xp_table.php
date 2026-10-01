<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_xp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('total_xp', 12, 2)->default(0.00);
            $table->decimal('ex_char_xp', 12, 2)->default(0.00);
            $table->decimal('ex_bailiff_xp', 12, 2)->default(0.00);
            $table->decimal('wiki_xp', 12, 2)->default(0.00);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_xp');
    }
};

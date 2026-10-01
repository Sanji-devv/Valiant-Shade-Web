<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('character_type', ['MAIN', 'CLERK'])->default('MAIN');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'TRAVELER', 'KIA', 'MIA'])->default('ACTIVE');
            $table->foreignId('rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->integer('current_level')->default(1);
            $table->decimal('allowance_sp', 14, 2)->default(0.00);

            $table->decimal('total_xp', 12, 2)->default(0.00);
            $table->decimal('rank_floor_xp', 12, 2)->default(0.00);
            $table->decimal('quest_xp', 12, 2)->default(0.00);
            $table->decimal('report_xp', 12, 2)->default(0.00);
            $table->decimal('bailiff_xp', 12, 2)->default(0.00);
            $table->decimal('ex_char_xp', 12, 2)->default(0.00);
            $table->decimal('wiki_xp', 12, 2)->default(0.00);

            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('active_main_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('active_clerk_character_id')->references('id')->on('characters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_main_character_id']);
            $table->dropForeign(['active_clerk_character_id']);
        });

        Schema::dropIfExists('characters');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_graveyard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->enum('cause', ['KIA', 'MIA']);
            
            $table->integer('quest_count_at_death')->default(0);
            $table->decimal('quest_xp_at_death', 12, 2)->default(0.00);
            $table->decimal('report_xp_at_death', 12, 2)->default(0.00);
            $table->decimal('transferred_xp', 12, 2)->default(0.00);
            $table->timestamp('recorded_at')->useCurrent();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_graveyard');
    }
};
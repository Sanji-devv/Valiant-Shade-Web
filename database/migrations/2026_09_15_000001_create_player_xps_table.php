<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_xps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('character_name');
            $table->string('player_name')->nullable();
            $table->string('status')->default('active');
            $table->string('rank')->default('Aday');
            $table->integer('level')->default(1);
            $table->integer('sessions_count')->default(1);
            $table->integer('rank_xp')->default(0);
            $table->integer('mission_xp')->default(0);
            $table->integer('report_xp')->default(0);
            $table->integer('bailiff_xp')->default(0);
            $table->integer('extra_xp')->default(0);
            $table->integer('bonus_xp')->default(0);
            $table->integer('total_xp')->default(0);
            $table->integer('inactive_threshold_xp')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('character_name');
            $table->index('player_name');
            $table->index('status');
            $table->index('rank');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_xps');
    }
};
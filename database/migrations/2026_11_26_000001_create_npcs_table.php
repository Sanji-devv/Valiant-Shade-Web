<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->string('title', 255)->nullable();
            $table->string('profession', 255)->nullable();
            $table->string('race', 120)->nullable();
            $table->string('faction', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('status', 60)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->text('summary')->nullable();
            $table->longText('background')->nullable();
            $table->text('notes')->nullable();
            $table->text('search_name');
            $table->longText('search_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npcs');
    }
};

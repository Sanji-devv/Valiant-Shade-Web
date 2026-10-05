<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bailiffs', function (Blueprint $table) {
            $table->id();
            $table->string('character_name');
            $table->string('role');
            $table->string('assigned_date')->nullable(); 
            $table->string('status')->default('active');
            $table->integer('order_index')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('character_name');
            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bailiffs');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_reader_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->char('reader_hash', 64);
            $table->string('reaction', 24);
            $table->timestamps();
            $table->unique(['news_id', 'reader_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_reader_reactions');
    }
};

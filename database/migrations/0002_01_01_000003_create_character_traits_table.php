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
            $table->string('character_name')->index();
            $table->integer('level')->default(1);
            $table->string('slot_type')->default('standard');
            $table->string('trait_name');
            $table->string('category')->nullable();
            $table->string('prerequisites')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_class_feat')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['character_name', 'level']);
            $table->index('slot_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_feats');
    }
};
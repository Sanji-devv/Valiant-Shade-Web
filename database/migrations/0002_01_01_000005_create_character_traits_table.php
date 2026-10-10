<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('character_traits')) {
            Schema::create('character_traits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('character_id')->nullable()->constrained('characters')->cascadeOnDelete();
                $table->foreignId('trait_id')->nullable()->constrained('traits')->nullOnDelete();
                $table->string('character_name')->nullable()->index();
                $table->string('slot_key')->nullable()->index();
                $table->string('slot_name')->nullable();
                $table->string('required_rank')->nullable();
                $table->integer('required_hierarchy')->default(0);
                $table->string('trait_name')->nullable();
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->text('source_url')->nullable();
                $table->text('notes')->nullable();
                $table->integer('rank')->default(0);

                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('character_traits');
    }
};

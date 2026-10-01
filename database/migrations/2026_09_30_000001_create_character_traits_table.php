<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('traits')) {
            Schema::create('traits', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('category')->nullable()->index();
                $table->text('description')->nullable();
                $table->text('source_url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('character_traits')) {
            Schema::create('character_traits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('character_id')->nullable()->constrained('characters')->cascadeOnDelete();
                $table->string('character_name')->index();
                $table->string('slot_key'); // 'uye', 'kidemli', 'uzman'
                $table->string('slot_name')->default('Üye'); // 'Üye', 'Kıdemli', 'Uzman'
                $table->string('required_rank')->default('Üye'); // 'Üye', 'Kıdemli', 'Uzman'
                $table->integer('required_hierarchy')->default(3); // 3 (Üye), 4 (Kıdemli), 5 (Uzman)
                $table->string('trait_name')->nullable();
                $table->string('category')->nullable(); // Combat, Regional, Magic, Faith, Social, Nature...
                $table->text('description')->nullable();
                $table->string('source_url')->nullable();
                $table->timestamps();

                $table->unique(['character_name', 'slot_key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_traits');
        Schema::dropIfExists('traits');
    }
};

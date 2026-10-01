<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category')->default('Lonca Duyurusu');
                $table->string('badge_color')->default('primary');
                $table->string('category_icon')->default('icon-base ti tabler-trophy');
                $table->string('tag')->nullable();
                $table->string('tag_icon')->nullable()->default('icon-base ti tabler-sparkles');
                $table->string('date_text')->nullable();
                $table->text('description');
                $table->string('image_url');
                $table->json('stats')->nullable();
                $table->string('button_text')->nullable();
                $table->string('button_url')->nullable();
                $table->string('button_color')->default('primary');
                $table->string('button_icon')->nullable()->default('icon-base ti tabler-shield-check');
                $table->string('location_badge')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
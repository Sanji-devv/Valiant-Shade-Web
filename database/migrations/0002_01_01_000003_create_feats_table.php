<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feats')) {
            Schema::create('feats', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique()->index();
                $table->string('category')->nullable(); // COMBAT, GENERAL, METAMAGIC, ITEM_CREATION, RACIAL
                $table->text('prerequisites')->nullable();
                $table->text('benefit');
                $table->text('normal_effect')->nullable();
                $table->text('special')->nullable();
                $table->text('external_url')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feats');
    }
};
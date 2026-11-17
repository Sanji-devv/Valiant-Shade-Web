<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('god_domain_overrides', function (Blueprint $table) {
            $table->string('name', 120)->nullable();
            $table->string('epithet')->nullable();
            $table->text('summary')->nullable();
            $table->text('profile_text')->nullable();
            $table->string('profile_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('god_domain_overrides', function (Blueprint $table) {
            $table->dropColumn(['name', 'epithet', 'summary', 'profile_text', 'profile_image']);
        });
    }
};

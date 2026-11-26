<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('god_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->nullable();
            $table->string('corrects_slug', 120)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('approval_status', 32)->default('pending');
            $table->string('name', 120);
            $table->string('epithet', 255)->nullable();
            $table->string('alignment', 100)->nullable();
            $table->string('category', 32)->default('minor');
            $table->json('domains')->nullable();
            $table->json('subdomains')->nullable();
            $table->string('weapon', 255)->nullable();
            $table->text('summary')->nullable();
            $table->longText('profile_text')->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->text('submitter_notes')->nullable();
            $table->text('search_content')->nullable();
            $table->timestamps();

            $table->index('approval_status');
            $table->index(['approval_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('god_submissions');
    }
};
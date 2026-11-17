<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_view_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->unsignedInteger('client_port')->nullable();
            $table->boolean('counted')->default(false);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['news_id', 'ip_address', 'created_at']);
            $table->index(['news_id', 'counted', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_view_logs');
    }
};
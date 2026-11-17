<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('god_domain_overrides', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('alignment', 100)->nullable();
            $table->json('domains');
            $table->json('subdomains');
            $table->string('weapon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('god_domain_overrides');
    }
};

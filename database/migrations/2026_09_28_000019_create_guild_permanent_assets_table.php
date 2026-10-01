<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guild_permanent_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price_sp', 12, 2)->default(0.00);
            $table->decimal('total_price_sp', 12, 2)->default(0.00);

            // Asset Governance & Main Character Link
            $table->foreignId('custodian_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->enum('asset_category', [
                'MOUNT',
                'VEHICLE',
                'PROPERTY',
                'DEED_CONTRACT',
                'RELIC',
                'INFRASTRUCTURE'
            ])->default('PROPERTY');
            $table->text('notes_and_lore')->nullable();
            $table->timestamp('acquired_at')->useCurrent();

            $table->timestamps();

            $table->index('asset_name');
            $table->index('asset_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_permanent_assets');
    }
};

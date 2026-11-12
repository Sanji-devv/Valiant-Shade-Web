<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('sell_price', 14, 2)->default(0)->after('buy_price');
        });

        DB::table('inventory_items')->update([
            'sell_price' => DB::raw('ROUND(total_price * 0.50 / CASE WHEN quantity > 0 THEN quantity ELSE 1 END, 2)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('sell_price');
        });
    }
};

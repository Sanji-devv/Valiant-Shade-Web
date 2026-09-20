<?php

namespace Database\Seeders;

use App\ValiantShade\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->inventoryService->resetToExcelDefaults();
    }
}

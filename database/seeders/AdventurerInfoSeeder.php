<?php

namespace Database\Seeders;

use App\Models\GuildBailiff;
use App\Models\GuildSetting;
use App\Models\GuildVaultItem;
use Illuminate\Database\Seeder;

class AdventurerInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $primaryPath = file_exists('C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/Maceracı İnfo.json')
            ? 'C:/Users/erdem/Desktop/PROJECTS/Utility/JSONS/Maceracı İnfo.json'
            : 'C:/Users/erdem/Desktop/PROJECTS/Utility/EXCEL_JSONS/Maceracı İnfo.json';
        $fallbackPath = file_exists(database_path('data/jsons/Maceracı İnfo.json'))
            ? database_path('data/jsons/Maceracı İnfo.json')
            : database_path('data/excel_jsons/Maceracı İnfo.json');
        $maceraciFile = file_exists($fallbackPath) ? $fallbackPath : (file_exists($primaryPath) ? $primaryPath : $fallbackPath);

        if (!file_exists($maceraciFile)) {
            $this->command?->error("Maceracı İnfo.json not found at {$maceraciFile}");
            return;
        }

        $maceraciData = json_decode(file_get_contents($maceraciFile), true) ?: [];

        // 1. Seed Guild Settings
        GuildSetting::set('guild_level', 12);
        GuildSetting::set('next_level_points', 546);
        GuildSetting::set('current_level_points', 454);

        // 2. Seed Guild Bailiff Council (7 Officers from columns S-U)
        $bailiffDefinitions = [
            ['character_name' => 'Thoma Quickfire', 'role' => 'Kahraman Kaynakları', 'assigned_date' => '14/7', 'order_index' => 1],
            ['character_name' => 'Temran',           'role' => 'Envanter',            'assigned_date' => '13/9', 'order_index' => 2],
            ['character_name' => 'Mirang',           'role' => 'Lonca Temsilcisi',     'assigned_date' => '27/8', 'order_index' => 3],
            ['character_name' => 'Zandar',           'role' => 'Questmaster',         'assigned_date' => '25/8', 'order_index' => 4],
            ['character_name' => 'Mart',             'role' => 'Arşivci',             'assigned_date' => '27/8', 'order_index' => 5],
            ['character_name' => 'Yanek',            'role' => 'Disiplin',            'assigned_date' => '3/7',  'order_index' => 6],
            ['character_name' => 'Bart',             'role' => 'Oathkeeper',          'assigned_date' => '13/1', 'order_index' => 7],
        ];

        foreach ($bailiffDefinitions as $b) {
            GuildBailiff::updateOrCreate(
                ['character_name' => $b['character_name']],
                [
                    'role' => $b['role'],
                    'assigned_date' => $b['assigned_date'],
                    'status' => 'active',
                    'order_index' => $b['order_index'],
                ]
            );
        }

        // 3. Seed Guild Vault Items from columns J-Q
        foreach ($maceraciData as $row) {
            $itemName = trim((string)($row['İsim_2'] ?? ''));
            if ($itemName === '' || $itemName === 'İsim_2' || $itemName === 'Eşya') {
                continue;
            }

            $rawType = trim((string)($row['Tip'] ?? 'Misc'));
            $itemType = match (strtolower($rawType)) {
                'armor', 'zırh' => 'Armor',
                'weapon', 'silah' => 'Weapon',
                'ammunition', 'mühimmat' => 'Ammunition',
                'gear', 'ekipman' => 'Gear',
                default => 'Misc',
            };

            $quality = !empty($row['Quality']) ? trim((string)$row['Quality']) : null;
            $qty = max(1, (int)($row['Adet'] ?? 1));
            $unitPrice = (float)($row['Birim Fiyatı'] ?? 0);
            $totalPrice = (float)($row['Tutarı'] ?? ($qty * $unitPrice));
            $marketValue = (float)($row['Ederi'] ?? round($totalPrice * 0.55, 2));

            GuildVaultItem::updateOrCreate(
                [
                    'item_name' => $itemName,
                    'quality' => $quality,
                ],
                [
                    'item_type' => $itemType,
                    'quantity' => $qty,
                    'unit_price_sp' => $unitPrice,
                    'total_price_sp' => $totalPrice,
                    'market_value_sp' => $marketValue,
                    'purchase_cost_sp' => $unitPrice,
                    'is_in_vault' => true,
                ]
            );
        }
    }
}

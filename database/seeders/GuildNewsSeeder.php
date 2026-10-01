<?php

namespace Database\Seeders;

use App\Models\GuildNews;
use Illuminate\Database\Seeder;

class GuildNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Ejderha Zirvesi ve Antik Harabeler Fethi Tamamlandı!',
                'category' => 'Sefer Zaferi',
                'badge_color' => 'warning',
                'category_icon' => 'icon-base ti tabler-trophy',
                'tag' => 'Son Keşif Raporu',
                'tag_icon' => 'icon-base ti tabler-sparkles',
                'date_text' => 'Bugün, 04:30',
                'description' => "Kıdemli keşif birliğimiz Kadim Ejderha Ignis'i dize getirerek kutsal kalıntıları kurtardı. Mahzene aktarılan efsanevi ganimetler ve sefer tecrübesi lonca kütüğüne işlendi.",
                'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
                'stats' => [
                    ['label' => 'Kazanılan Tecrübe', 'value' => '+12.5k XP', 'color' => 'text-warning'],
                    ['label' => 'Efsanevi Eşya', 'value' => '3 Kalıntı', 'color' => 'text-info'],
                    ['label' => 'Bölge Güvenliği', 'value' => '%100', 'color' => 'text-success'],
                    ['label' => 'Birlik Durumu', 'value' => '0 Kayıp', 'color' => 'text-white'],
                ],
                'button_text' => 'Karakter Kütüğü & Rapor',
                'button_url' => '/valiant-shade/character/sheet',
                'button_color' => 'primary',
                'button_icon' => 'icon-base ti tabler-shield-check',
                'location_badge' => 'Ejderha Zirvesi - Harabeler',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Aylık Lonca Maaşları & Görev Ödülleri Dağıtıldı',
                'category' => 'Hazine & Ödenekler',
                'badge_color' => 'success',
                'category_icon' => 'icon-base ti tabler-coins',
                'tag' => 'Kraliyet Sözleşmesi',
                'tag_icon' => 'icon-base ti tabler-building-bank',
                'date_text' => 'Aylık Dönem',
                'description' => 'Kraliyet sözleşmelerinden ve koruma anlaşmalarından elde edilen 45.000 Altın (GP) mahzene aktarıldı. Aktif korucular, büyücüler ve şampiyonlar için rütbe payları hazır.',
                'image_url' => 'static/valiant-shade/assets/img/news/guild_treasury_vault.jpg',
                'stats' => [
                    ['label' => 'Lonca Kasası', 'value' => '45,000 GP', 'color' => 'text-warning'],
                    ['label' => 'Maaş Dağıtımı', 'value' => '28 Üye', 'color' => 'text-info'],
                    ['label' => 'Aylık Büyüme', 'value' => '+18.2%', 'color' => 'text-success'],
                    ['label' => 'Finanse Edildi', 'value' => '4 Sefer', 'color' => 'text-white'],
                ],
                'button_text' => 'Mahzen & Envanteri Aç',
                'button_url' => '/valiant-shade/pages/inventory',
                'button_color' => 'success',
                'button_icon' => 'icon-base ti tabler-building-vault',
                'location_badge' => 'Kasa Güvencesi: Aktif',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Gölge Diyarı & Obsidyen Hisar Seferi Başlıyor!',
                'category' => 'Acil Sefer Çağrısı',
                'badge_color' => 'danger',
                'category_icon' => 'icon-base ti tabler-flame',
                'tag' => 'Yeni Akın',
                'tag_icon' => 'icon-base ti tabler-swords',
                'date_text' => 'Yüksek Tehlike',
                'description' => 'Obsidyen Hisar kapılarındaki boyutsal yarıklar tespit edildi. Seviye 12+ tüm savaşçılar, korucular ve mistikler Lonca Meclisi tarafından göreve çağrılmaktadır.',
                'image_url' => 'static/valiant-shade/assets/img/news/guild_shadow_realm.jpg',
                'stats' => [
                    ['label' => 'Zorluk Derecesi', 'value' => 'CR 14+', 'color' => 'text-danger'],
                    ['label' => 'Ekip Kontenjanı', 'value' => '4/6 Kişi', 'color' => 'text-warning'],
                    ['label' => 'Hedef Bölge', 'value' => 'Obsidyen', 'color' => 'text-info'],
                    ['label' => 'Beklenen Ganimet', 'value' => 'Efsanevi', 'color' => 'text-success'],
                ],
                'button_text' => 'Sefer Takımına Katıl',
                'button_url' => '/valiant-shade/pages/profile-teams',
                'button_color' => 'danger',
                'button_icon' => 'icon-base ti tabler-users-group',
                'location_badge' => 'Kalan Süre: 2 Gün',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($items as $data) {
            GuildNews::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}

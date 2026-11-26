<?php

namespace Database\Seeders;

use App\Models\Npc;
use App\Models\NpcTitle;
use App\ValiantShade\Services\NpcTitleSync;
use App\ValiantShade\Support\NpcImageStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Temizler ve halka açık sicil için ~10 örnek NPC oluşturur (onaylı, görselli).
 *
 * php artisan db:seed --class=NpcRosterDemoSeeder
 */
class NpcRosterDemoSeeder extends Seeder
{
    public function run(): void
    {
        $titleSync = app(NpcTitleSync::class);

        Npc::query()->each(function (Npc $npc): void {
            NpcImageStore::delete($npc->image_path);
        });

        DB::table('npc_npc_title')->delete();
        Npc::query()->delete();
        NpcTitle::query()->delete();

        $rows = [
            [
                'first_name' => 'Mirang',
                'last_name' => 'Cıvıltısı',
                'profession' => 'Gnom bilgin',
                'race' => 'Gnome',
                'faction' => 'Feldan Krallığı',
                'location' => 'Küçük Liman — Bilgin Kulesi',
                'status' => 1,
                'titles' => ['Gnom Temsilcisi', 'Parşömen Ustası'],
                'image' => 'guild_arcane_academy.jpg',
                'summary' => 'Lağım gazetesinin sık konuğu; eski çağ haritalarını çözüp liman ticaretini kayda geçirir.',
                'background' => 'Yüz yılı aşkın süredir Feldan arşivlerinde yaşar sayılır. Son yıllarda lonca kayıtlarını halka açık sicile aktarmak için gönüllü oldu.',
            ],
            [
                'first_name' => 'Keth',
                'mid_name' => 'Demir',
                'last_name' => 'Runestone',
                'profession' => 'Usta demirci',
                'race' => 'Dwarf',
                'faction' => 'Taş Lonca',
                'location' => 'Feldan — Cephanelik Mahallesi',
                'status' => 1,
                'titles' => ['Taş Lonca Efendisi'],
                'image' => 'guild_forge_armory.jpg',
                'summary' => 'Krallık muhafızlarının zırhlarını döver; söylentiye göre ejderha pullu bir kılıç üzerinde çalışıyor.',
            ],
            [
                'first_name' => 'Elira',
                'last_name' => 'Veymar',
                'profession' => 'Saray arşivcisi',
                'race' => 'Human',
                'faction' => 'Feldan sarayı',
                'location' => 'Taş Şehir — Saray Arşivi',
                'status' => 1,
                'titles' => ['Kraliyet Kâtibi'],
                'image' => 'guild_hall_council.jpg',
                'summary' => 'Resmî senetlerin ve lonca antlaşmalarının bekçisi.',
                'notes' => 'NPC gönder kuyruğunu denemek için kullanmayın — kalıcı örnek kayıt.',
            ],
            [
                'first_name' => 'Thorne',
                'last_name' => 'Ashveil',
                'profession' => 'Gözcü',
                'race' => 'Half-Elf',
                'faction' => 'Bağımsız',
                'location' => 'Mistik Orman sınırı',
                'status' => 3,
                'titles' => ['Orman Yolu Bilicisi'],
                'image' => 'guild_mystic_forest.jpg',
                'summary' => 'Son haberlerde Batı patikasında kayboldu; sadece küllü bir pusula bulundu.',
                'background' => 'İki ay önce gölge diyarına bakan gözetleme karakolundan haber bekleniyordu.',
            ],
            [
                'first_name' => 'Althea',
                'last_name' => 'Güneşçi',
                'profession' => 'Şifacı rahibe',
                'race' => 'Human',
                'faction' => 'Göksel Tapınak',
                'location' => 'Feldan — Kutsal Avlu',
                'status' => 1,
                'titles' => ['Işık Hizmetkârı'],
                'image' => 'guild_celestial_sanctuary.jpg',
                'summary' => 'Savaş gazilerini iyileştirir; undead salgınında cephede kalmıştı.',
            ],
            [
                'first_name' => 'Duneward',
                'last_name' => 'Saltbreak',
                'profession' => 'Liman kaptanı',
                'race' => 'Human',
                'faction' => 'Feldan donanması',
                'location' => 'Küçük Liman — Rıhtım',
                'status' => 1,
                'titles' => ['Deniz Kurdu'],
                'image' => 'guild_port_expedition.jpg',
                'summary' => 'Doğu seferlerinin lojistiğini yönetir; korsanlık raporlarını doğrudan saraya iletir.',
            ],
            [
                'first_name' => 'Sessiz',
                'last_name' => 'Barrow',
                'profession' => 'Han işletmecisi',
                'race' => 'Halfling',
                'faction' => 'Lonca dostu',
                'location' => 'Feldan — Fısıltı Hanı',
                'status' => 9,
                'titles' => ['Han Reisi'],
                'image' => 'guild_tavern_whispers.jpg',
                'summary' => 'Emekli maceracı; hanın arka odasında sürekli akan söylenti defteri tutar.',
            ],
            [
                'first_name' => 'Vex',
                'last_name' => 'Ambermote',
                'profession' => 'Büyü teorisyeni',
                'race' => 'Elf',
                'faction' => 'Arkana Akademisi',
                'location' => 'Feldan — Akademi Kütüphanesi',
                'status' => 1,
                'titles' => ['Büyü Profesörü', 'Rün Araştırmacısı'],
                'image' => 'guild_arcane_academy.jpg',
                'summary' => 'Ejderha kanı iksirleri üzerine tartışmalı tezler yazar.',
            ],
            [
                'first_name' => 'Grimjaw',
                'last_name' => 'Ironfist',
                'profession' => 'Arena şampiyonu',
                'race' => 'Half-Orc',
                'faction' => 'Turnuva Ligi',
                'location' => 'Feldan — Büyük Arena',
                'status' => 2,
                'titles' => ['Demir Yumruk'],
                'image' => 'guild_arena_tournament.jpg',
                'summary' => 'Son turnuvada şampiyon olmuştu; üç ay sonra zehirli bir bıçakla ölü bulundu.',
                'background' => 'Cenaze töreni lonca kahramanları sayfasında anıldı.',
            ],
            [
                'first_name' => 'Lyra',
                'last_name' => 'Nocturne',
                'profession' => 'Gölge arşivcisi',
                'race' => 'Human',
                'faction' => 'Bilinmiyor',
                'location' => 'Yeraltı kriptleri',
                'status' => 8,
                'titles' => ['Gece Yazarı'],
                'image' => 'guild_dungeon_crypt.jpg',
                'summary' => 'Çalınan lonca kayıtlarının peşinde; mahkeme celbi altında.',
                'background' => 'Gölgeler diyarına açılan kapı haritasını çalmakla suçlanıyor.',
            ],
        ];

        foreach ($rows as $row) {
            $titles = $row['titles'];
            unset($row['titles']);
            $imageFile = $row['image'];
            unset($row['image']);

            $row['approval_status'] = Npc::APPROVAL_APPROVED;
            $row['image_path'] = $this->copyDemoPortrait($imageFile, $row['first_name'], $row['last_name']);

            $npc = Npc::create($row);
            $titleSync->sync($npc, $titles);
            $npc->refreshSearchFromTitles();
        }

        $this->command?->info(sprintf('NPC: %d onaylı örnek kayıt oluşturuldu.', count($rows)));
    }

    private function copyDemoPortrait(string $sourceFile, string $first, string $last): string
    {
        $source = public_path('static/valiant-shade/assets/img/news/'.$sourceFile);
        if (! File::isFile($source)) {
            return 'static/valiant-shade/assets/img/news/'.$sourceFile;
        }

        $slug = Str::slug($first.'_'.$last) ?: 'npc';
        $filename = 'demo_'.$slug.'_'.Str::lower(Str::random(6)).'.jpg';
        $relative = NpcImageStore::DIRECTORY.'/'.$filename;

        Storage::disk(NpcImageStore::DISK)->makeDirectory(NpcImageStore::DIRECTORY);
        Storage::disk(NpcImageStore::DISK)->put($relative, File::get($source));

        return 'storage/'.$relative;
    }
}
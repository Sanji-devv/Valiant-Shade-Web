<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\News;
use App\ValiantShade\Services\GazetteIssueService;
use App\ValiantShade\Services\PublicNewsService;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Support\NewsEngagement;
use App\ValiantShade\Support\NewsStats;
use Database\Seeders\NewsSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * NewsController handles displaying rich guild news, rumors, death notices,
 * expedition chronicles, and council decrees without direct database dependency.
 */
class NewsController extends Controller
{
    /**
     * Get the master static news dataset (English by default, Turkish if locale is 'tr').
     */
    public static function getNewsData(): Collection
    {
        $isTr = app()->getLocale() === 'tr';

        if ($isTr) {
            $rawNews = [
                [
                    'id' => 1,
                    'title' => 'GÃÂ¶lge Dehlizleri Seferinde AÄÅ¸ÄÂ±r KayÄÂ±p: BaÅÅ¸ MuhafÄÂ±z Braum ve 4 KeÅÅ¸if Eri ÅÂehit DÃÂ¼ÅÅ¸tÃÂ¼',
                    'category' => 'Ãâ€“lÃÂ¼m & ÅÂehitler',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-skull',
                    'tag' => 'Lonca YasÄÂ± & Anma',
                    'tag_icon' => 'icon-base ti tabler-candle',
                    'date_text' => 'BugÃÂ¼n 04:15',
                    'published_date' => '01 Ekim 2026, 04:15',
                    'read_time' => '4 dk okuma',
                    'image_file' => 'guild_fallen_memorial.jpg',
                    'location_badge' => 'GÃÂ¶lge Dehlizleri - 3. MÃÂ¼hÃÂ¼rlÃÂ¼ Kat',
                    'author' => 'Lonca BaÅÅ¸kÃÂ¢tibi Eldrin',
                    'author_role' => 'YÃÂ¼ksek Divan KÄÂ±demli YazmanÄÂ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/1.png',
                    'is_breaking' => true,
                    'description' => "GÃÂ¶lge Dehlizleri'nin 3. katÄÂ±nda Kadim Lich Malgok'un beklenmedik nekromansi pususuna yakalanan 1. KeÅÅ¸if Kolu'nda BaÅÅ¸ MuhafÄÂ±z Braum 'DemiryÃÂ¼rek' ve beraberindeki 4 genÃÂ§ savaÅÅ¸ÃÂ§ÄÂ± ÅÅ¸ehadet ÅÅ¸erbetini iÃÂ§ti. Lonca merkezinde 3 gÃÂ¼nlÃÂ¼k resmi yas ilan edildi.",
                    'full_story' => "Valiant Shade YÃÂ¼ksek KomutanlÄÂ±ÄÅ¸ÄÂ± tarafÄÂ±ndan sabaha karÅÅ¸ÄÂ± yayÄÂ±nlanan acÄÂ± bildiriye gÃÂ¶re; GÃÂ¶lge Dehlizleri'nin mÃÂ¼hÃÂ¼rlÃÂ¼ 3. seviyesine rutin haritalama amacÄÂ±yla inen 'GÃÂ¼mÃÂ¼ÅÅ¸ PenÃÂ§e' devriye birliÄÅ¸i, kadim haritalarda iÅÅ¸aretlenmemiÅÅ¸ bir nekromansi pususuna dÃÂ¼ÅÅ¸tÃÂ¼.

BirliÄÅ¸e liderlik eden 28 yÄÂ±llÄÂ±k kÄÂ±demli BaÅÅ¸ MuhafÄÂ±z Braum 'DemiryÃÂ¼rek', arkasÄÂ±ndaki acemi bÃÂ¼yÃÂ¼cÃÂ¼leri ve yaralÄÂ± izcileri kurtarabilmek iÃÂ§in yÄÂ±kÄÂ±lan mermer kemerin altÄÂ±nda tek baÅÅ¸ÄÂ±na kalkan oluÅÅ¸turdu. YanÄÂ±ndaki cesur silah arkadaÅÅ¸larÄÂ± Kolcu Kaelen, ÅÂÃÂ¶valye Varis, Ãâ€¡ÄÂ±rak Arcanist Alistair ve SÄÂ±hhiyeci Lyanna, son nefeslerine kadar mevzilerini terk etmedi.

Hayatta kalan izcilerin ifadesine gÃÂ¶re, Braum kalkanÄÂ±nÄÂ± dehlizin giriÅÅ¸ine mÄÂ±hlayarak arkasÄÂ±ndaki 8 lonca ÃÂ¼yesinin gÃÂ¼venle tahliye olmasÄÂ±nÄÂ± saÄÅ¸ladÄÂ±. ÅÂehitlerimizin naaÅÅ¸larÄÂ±, Lonca ÅÂÃÂ¶valyeleri tarafÄÂ±ndan sabahÄÂ±n ilk ÄÂ±ÅÅ¸ÄÂ±klarÄÂ±yla Meclis ÅÂapeli'ne getirildi.

Lonca UstasÄÂ± Lord Gerald fermanÄÂ±yla: Valiant Shade bayraklarÄÂ± yarÄÂ±ya indirilmiÅÅ¸, bÃÂ¼yÃÂ¼k salonda anma sunaÄÅ¸ÄÂ± kurulmuÅÅ¸ ve ÅÅ¸ehitlerimizin ailelerine Lonca Hazinesi'nden ÃÂ¶mÃÂ¼r boyu maaÅÅ¸ tahsis edilmiÅÅ¸tir. RuhlarÄÂ± Kadim IÅÅ¸ÄÂ±k'ta huzur bulsun.",
                    'testimony' => "Braum son nefesinde kalkanÄÂ±nÄÂ± yere vurdu ve 'ArkamÄÂ± kollamayÄÂ± bÄÂ±rakÄÂ±n, ÃÂ§ÄÂ±raklarÄÂ± dehlizden ÃÂ§ÄÂ±karÄÂ±n!' diye kÃÂ¼kredi. Onun sayesinde bugÃÂ¼n nefes alabiliyoruz. ââ‚¬â€ Kurtulan ÄÂ°vece Kolcusu Finn",
                    'stats' => [
                        ['label' => 'ÅÂehit SayÄÂ±sÄÂ±', 'value' => '5 Kahraman', 'color' => 'text-danger'],
                        ['label' => 'KurtarÄÂ±lan Can', 'value' => '8 ÄÂ°vece', 'color' => 'text-success'],
                        ['label' => 'Lonca Durumu', 'value' => '3 GÃÂ¼n Yas', 'color' => 'text-warning'],
                        ['label' => 'Tehlike Kodu', 'value' => 'KÄÂ±rmÄÂ±zÄÂ± (Lich)', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Taziye Defteri & Detaylar',
                    'button_url' => '/valiant-shade/pages/news#news-1',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-candle',
                    'order' => 1,
                    'is_active' => true,
                    'views_count' => 1420,
                ],
                [
                    'id' => 2,
                    'title' => 'KÄÂ±zÄÂ±l Han FÄÂ±sÄÂ±ltÄÂ±larÄÂ±: Valiant Shade YÃÂ¶neticisinin GÃÂ¶lge Cemiyeti ile Gizli Gece BuluÅÅ¸masÄÂ±!',
                    'category' => 'Dedikodu & Skandal',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flame',
                    'tag' => 'Meyhane Dedikodusu',
                    'tag_icon' => 'icon-base ti tabler-message-2-question',
                    'date_text' => 'DÃÂ¼n Gece YarÄÂ±sÄÂ±',
                    'published_date' => '30 EylÃÂ¼l 2026, 23:45',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_tavern_whispers.jpg',
                    'location_badge' => 'KÄÂ±zÄÂ±l Han - Arka Loca No: 4',
                    'author' => 'Meyhaneci Barnaby',
                    'author_role' => 'KÄÂ±zÄÂ±l Han BaÅÅ¸ ÄÂ°ÅÅ¸letmecisi & ÅÂehir GÃÂ¶zcÃÂ¼sÃÂ¼',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/2.png',
                    'is_breaking' => false,
                    'description' => "KÄÂ±zÄÂ±l Han'ÄÂ±n loÅÅ¸ arka masalarÄÂ±nda dÃÂ¼n gece fÄÂ±sÄÂ±ltÄÂ±lar koptu: Valiant Shade YÃÂ¼ksek Meclisi'nden kukuletalÄÂ± bir yÃÂ¶neticinin, yasaklÄÂ± GÃÂ¶lge HÄÂ±rsÄÂ±zlar Cemiyeti sÃÂ¶zcÃÂ¼sÃÂ¼yle yÃÂ¼klÃÂ¼ miktarda kara altÄÂ±n ve kan mÃÂ¼hÃÂ¼rlÃÂ¼ bir antlaÅÅ¸ma karÅÅ¸ÄÂ±lÄÂ±ÄÅ¸ÄÂ±nda gizlice el sÄÂ±kÄÂ±ÅÅ¸tÄÂ±ÄÅ¸ÄÂ± iddia ediliyor!",
                    'full_story' => "KÄÂ±zÄÂ±l Han'ÄÂ±n fÄÂ±ÃÂ§ÄÂ±larÄÂ±n arkasÄÂ±ndaki loÅÅ¸ kÃÂ¶ÅÅ¸elerinde dÃÂ¼n gece ÃÂ¶yle fÄÂ±sÄÂ±ltÄÂ±lar dolaÅÅ¸tÄÂ± ki, cÃÂ¼celer bile biralarÄÂ±nÄÂ± yudumlamayÄÂ± bÄÂ±raktÄÂ±!

Saat gece yarÄÂ±sÄÂ±nÄÂ± ÃÂ¼ÃÂ§ ÃÂ§eyrek geÃÂ§e, saÄÅ¸ omzunda Valiant Shade'in altÄÂ±n sÄÂ±rmalÄÂ± kurdelesini taÅÅ¸ÄÂ±dÄÂ±ÄÅ¸ÄÂ± sÃÂ¶ylenen uzun boylu, gri pelerinli bir ÅÅ¸ahÄÂ±s hanÄÂ±n servis kapÄÂ±sÄÂ±ndan iÃÂ§eri sÃÂ¼zÃÂ¼ldÃÂ¼. Arka localardan 4 numaralÄÂ± masada, yÃÂ¼zÃÂ¼nde gÃÂ¼mÃÂ¼ÅÅ¸ bir tilki maskesi bulunan ve ÅÅ¸ehrin tekinsiz yeraltÄÂ± dÃÂ¼nyasÄÂ±ndan olduÄÅ¸u bilinen 'GÃÂ¶lge ElÃÂ§i' onu bekliyordu.

Masaya servis yapan garson cÃÂ¼ce Thorbjorn'un yeminle anlattÄÂ±ÄÅ¸ÄÂ±na gÃÂ¶re; masaya bÄÂ±rakÄÂ±lan kadife keseden dÃÂ¶kÃÂ¼len paralar sÄÂ±radan imparatorluk altÄÂ±nÄÂ± deÄÅ¸il, mÃÂ¼hÃÂ¼rlÃÂ¼ 'Kara ZÃÂ¼mrÃÂ¼t' sikkeleriydi! ÄÂ°kili, ÃÂ¼zerinde mavi bÃÂ¼yÃÂ¼ alevi tÃÂ¼ten bir antlaÅÅ¸ma parÅÅ¸ÃÂ¶menini kanla imzaladÄÂ±ktan sonra ÅÅ¸ehrin su kanallarÄÂ±na doÄÅ¸ru hÄÂ±zla gÃÂ¶zden kayboldu.

Lonca DivanÄÂ± iddialarÄÂ± henÃÂ¼z resmi olarak doÄÅ¸rulamasa da, Lonca HazinedarÄÂ±'nÄÂ±n son 2 haftadÄÂ±r kayÄÂ±p olan 'Obsidyen Mahzen AnahtarÄÂ±' ile bu gizli buluÅÅ¸manÄÂ±n bir baÄÅ¸ÄÂ± olup olmadÄÂ±ÄÅ¸ÄÂ± sokaklarda en ÃÂ§ok konuÅÅ¸ulan komplo teorisi haline geldi.",
                    'testimony' => "Kendi gÃÂ¶zlerimle gÃÂ¶rdÃÂ¼m efendim! O pelerinin altÄÂ±ndaki broÅÅ¸ dÃÂ¼pedÃÂ¼z YÃÂ¼ksek Meclis'in kartal armasÄÂ±ydÄÂ±. Kadehleri tokuÅÅ¸turmadÄÂ±lar bile, parayÄÂ± alÄÂ±p karanlÄÂ±ÄÅ¸a karÄÂ±ÅÅ¸tÄÂ±lar. ââ‚¬â€ CÃÂ¼ce Thorbjorn",
                    'stats' => [
                        ['label' => 'GÃÂ¼venilirlik', 'value' => '%75 ÄÂ°ddia', 'color' => 'text-warning'],
                        ['label' => 'GÃÂ¶rgÃÂ¼ TanÄÂ±ÄÅ¸ÄÂ±', 'value' => '3 Masada', 'color' => 'text-info'],
                        ['label' => 'ÅÂÃÂ¼pheli Belge', 'value' => 'Kan MÃÂ¼hÃÂ¼rlÃÂ¼', 'color' => 'text-danger'],
                        ['label' => 'RÃÂ¼ÅÅ¸vet Tahmini', 'value' => '5.000 Kara Sikke', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'FÄÂ±sÄÂ±ltÄÂ±yÄÂ± ÄÂ°ncele',
                    'button_url' => '/valiant-shade/pages/news#news-2',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-eye',
                    'order' => 2,
                    'is_active' => true,
                    'views_count' => 2180,
                ],
                [
                    'id' => 3,
                    'title' => "Lonca HazinedarÄÂ±'na Gece YarÄÂ±sÄÂ± Suikast GiriÅÅ¸imi: Zehirli Ok ve BÄÂ±rakÄÂ±lan Kara Mektup",
                    'category' => 'Suikast & ÄÂ°hanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-dagger',
                    'tag' => 'Acil GÃÂ¼venlik UyarÄÂ±sÄÂ±',
                    'tag_icon' => 'icon-base ti tabler-alert-triangle',
                    'date_text' => '12 saat ÃÂ¶nce',
                    'published_date' => '01 Ekim 2026, 10:30',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_conspiracy.jpg',
                    'location_badge' => 'Hazine Kulesi - MuhafÄÂ±z Avlusu',
                    'author' => 'MuhafÄÂ±z YÃÂ¼zbaÅÅ¸ÄÂ±sÄÂ± Torin',
                    'author_role' => 'Kale GÃÂ¼venliÄÅ¸i ve AsayiÅÅ¸ ÅÂefi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/3.png',
                    'is_breaking' => false,
                    'description' => "Lonca HazinedarÄÂ± Lord Vance, ÃÂ§alÄÂ±ÅÅ¸ma odasÄÂ±nda bÃÂ¼tÃÂ§e kayÄÂ±tlarÄÂ±nÄÂ± incelerken pencereyi delen zehirli bir tatar yayÄÂ± okuyla hedef alÄÂ±ndÄÂ±! Vance kÄÂ±l payÄÂ± kurtulurken, saldÄÂ±rganÄÂ±n geride bÄÂ±raktÄÂ±ÄÅ¸ÄÂ± parÅÅ¸ÃÂ¶mende 'Kasa aÃÂ§ÄÂ±lacak, bedel ÃÂ¶denecek' yazdÄÂ±ÄÅ¸ÄÂ± bildirildi.",
                    'full_story' => "Valiant Shade Hazine Dairesi dÃÂ¼n gece kanlÄÂ± bir suikast giriÅÅ¸imine sahne oldu. Saat 01:20 sularÄÂ±nda, lonca surlarÄÂ±nÄÂ±n doÄÅ¸u kulesinden ateÅÅ¸lenen gÃÂ¶lge iÅÅ¸lemeli zehirli bir arbalet oku, Lonca HazinedarÄÂ± Lord Vance'in tam kulaÄÅ¸ÄÂ±nÄÂ±n yanÄÂ±ndan geÃÂ§erek arkasÄÂ±ndaki meÅÅ¸e kitaplÄÂ±ÄÅ¸a saplandÄÂ±.

Okun ucunda sÃÂ¼rÃÂ¼len zehrin nadir bulunan 'Wyvern Nefesi' toksini olduÄÅ¸u simyacÄÂ±lar tarafÄÂ±ndan doÄÅ¸rulandÄÂ±. Tek bir sÄÂ±yrÄÂ±ÄÅ¸ÄÂ±n bile dakikalar iÃÂ§inde kalbi durdurmaya yeteceÄÅ¸i bu ÃÂ¶lÃÂ¼mcÃÂ¼l sÄÂ±vÄÂ±nÄÂ±n karaborsadan temin edildiÄÅ¸i dÃÂ¼ÅÅ¸ÃÂ¼nÃÂ¼lÃÂ¼yor.

SuikastÃÂ§ÄÂ±nÄÂ±n kaÃÂ§arken dÃÂ¼ÅÅ¸ÃÂ¼rdÃÂ¼ÄÅ¸ÃÂ¼ siyah ipek mendilde, Valiant Shade'in rakip hanedanlarÄÂ±na iÅÅ¸aret eden gizli bir kabartma mÃÂ¼hÃÂ¼r ve kÄÂ±sa bir not bulundu: 'Kasa aÃÂ§ÄÂ±lacak, bedel kanla ÃÂ¶denecek.'

MuhafÄÂ±z YÃÂ¼zbaÅÅ¸ÄÂ±sÄÂ± Torin kalede kÄÂ±rmÄÂ±zÄÂ± alarm ilan etti; Hazine Kulesi'ne giriÅÅ¸ ÃÂ§ÄÂ±kÄÂ±ÅÅ¸lar kÄÂ±sÄÂ±tlandÄÂ± ve tÃÂ¼m lonca ÃÂ¼yelerine ÅÅ¸ÃÂ¼pheli kiÅÅ¸ileri derhal bildirmeleri ÃÂ§aÄÅ¸rÄÂ±sÄÂ±nda bulunuldu.",
                    'testimony' => 'Ok meÅÅ¸e tahtaya girdiÄÅ¸inde havaya yeÅÅ¸il bir duman yÃÂ¼kseldi. Hedef doÄÅ¸rudan boynumdu. LoncamÄÂ±zÄÂ±n iÃÂ§indeki kÃÂ¶stebek bulunana dek kimseye huzur yok. ââ‚¬â€ Lord Vance',
                    'stats' => [
                        ['label' => 'Hedef', 'value' => 'Lord Vance', 'color' => 'text-white'],
                        ['label' => 'KullanÄÂ±lan Zehir', 'value' => 'Wyvern Nefesi', 'color' => 'text-danger'],
                        ['label' => 'Alarm Seviyesi', 'value' => 'Kod KÄÂ±rmÄÂ±zÄÂ±', 'color' => 'text-danger'],
                        ['label' => 'Kelle Ãâ€“dÃÂ¼lÃÂ¼', 'value' => '2.500 AltÄÂ±n', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'Suikast Raporu',
                    'button_url' => '/valiant-shade/pages/news#news-3',
                    'button_color' => 'dark',
                    'button_icon' => 'icon-base ti tabler-shield-alert',
                    'order' => 3,
                    'is_active' => true,
                    'views_count' => 1890,
                ],
                [
                    'id' => 4,
                    'title' => "Ejderha GeÃÂ§idi ÅÂehidi BaÅÅ¸ BÃÂ¼yÃÂ¼cÃÂ¼ Lyra iÃÂ§in BÃÂ¼yÃÂ¼k ÅÂapel'de GÃÂ¶rkemli Cenaze TÃÂ¶reni",
                    'category' => 'Ãâ€“lÃÂ¼m & Anma',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-flame-off',
                    'tag' => 'ÅÂapel TÃÂ¶reni & Veda',
                    'tag_icon' => 'icon-base ti tabler-ribbon-health',
                    'date_text' => '2 GÃÂ¼n Ãâ€“nce',
                    'published_date' => '29 EylÃÂ¼l 2026, 14:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_heroes_funeral.jpg',
                    'location_badge' => 'GÃÂ¶k TapÄÂ±naÄÅ¸ÄÂ± & ÅÂehitler MeydanÄÂ±',
                    'author' => 'YÃÂ¼ksek Patrik Aurelius',
                    'author_role' => 'GÃÂ¼neÅÅ¸ ÅÂapeli BaÅÅ¸ Rahibi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/4.png',
                    'is_breaking' => false,
                    'description' => "Ejderha GeÃÂ§idi Seferi'nde birliÄÅ¸ini korumak iÃÂ§in kendini alev hortumunun ÃÂ¶nÃÂ¼ne siper eden BaÅÅ¸ BÃÂ¼yÃÂ¼cÃÂ¼ Lyra Stormchaser, yÃÂ¼zlerce lonca ÃÂ¼yesi ve halkÄÂ±n katÄÂ±ldÄÂ±ÄÅ¸ÄÂ± gÃÂ¶rkemli bir tÃÂ¶renle sonsuzluÄÅ¸a uÄÅ¸urlandÄÂ±.",
                    'full_story' => "ÅÂehrin sokaklarÄÂ± dÃÂ¼n sabah erken saatlerden itibaren siyah bayraklar ve beyaz zambaklarla donatÄÂ±ldÄÂ±. Ejderha GeÃÂ§idi'nde alevler saÃÂ§an KÄÂ±rmÄÂ±zÄÂ± Ejderha'nÄÂ±n ateÅÅ¸ dalgasÄÂ±nÄÂ± 'YÄÂ±ldÄÂ±z KalkanÄÂ±' bÃÂ¼yÃÂ¼sÃÂ¼yle durduran ve 14 gencecik lonca ÃÂ¼yesinin hayatÄÂ±nÄÂ± kurtarÄÂ±rken bedenini feda eden BaÅÅ¸ BÃÂ¼yÃÂ¼cÃÂ¼ Lyra Stormchaser iÃÂ§in veda gÃÂ¼nÃÂ¼ydÃÂ¼.

GÃÂ¶k TapÄÂ±naÄÅ¸ÄÂ±'ndan ÅÂehitler MeydanÄÂ±'na kadar uzanan kortejde, loncanÄÂ±n en kÄÂ±demli 12 paladini Lyra'nÄÂ±n asasÄÂ±nÄÂ± ve bÃÂ¼yÃÂ¼cÃÂ¼ cÃÂ¼ppesini taÅÅ¸ÄÂ±dÄÂ±. Lonca UstasÄÂ±, Lyra'ya ÃÂ¶lÃÂ¼mÃÂ¼nden sonra 'Valiant Shade ÃÅ“stÃÂ¼n Cesaret ve FedakarlÄÂ±k MadalyasÄÂ±' takdim etti.

Lyra'nÄÂ±n vasiyeti ÃÂ¼zerine lonca kÃÂ¼tÃÂ¼phanesine baÄÅ¸ÄÂ±ÅÅ¸ladÄÂ±ÄÅ¸ÄÂ± kadim bÃÂ¼yÃÂ¼ parÅÅ¸ÃÂ¶menleri korumaya alÄÂ±nÄÂ±rken, adÄÂ± Lonca Meclis Salonu'nun giriÅÅ¸indeki AltÄÂ±n Mermer Kitabe'ye kazÄÂ±ndÄÂ±.",
                    'testimony' => "Alevler ÃÂ¼stÃÂ¼mÃÂ¼ze gelirken tek bir adÄÂ±m bile geri ÃÂ§ekilmedi. 'KaÃÂ§ÄÂ±n!' dedi, 'Ben ÄÂ±ÅÅ¸ÄÂ±ÄÅ¸ÄÂ± tutarÄÂ±m'. O ÄÂ±ÅÅ¸ÄÂ±k hiÃÂ§ sÃÂ¶nmeyecek. ââ‚¬â€ Ãâ€¡ÄÂ±rak BÃÂ¼yÃÂ¼cÃÂ¼ Selene",
                    'stats' => [
                        ['label' => 'KurtardÄÂ±ÄÅ¸ÄÂ± Can', 'value' => '14 ÃÅ“ye', 'color' => 'text-success'],
                        ['label' => 'TÃÂ¶ren KatÄÂ±lÄÂ±mÄÂ±', 'value' => '800+ KiÅÅ¸i', 'color' => 'text-info'],
                        ['label' => 'Liyakat NiÅÅ¸anÄÂ±', 'value' => 'AltÄÂ±n Madalya', 'color' => 'text-warning'],
                        ['label' => 'AnÄÂ±t Kabri', 'value' => 'BÃÂ¼yÃÂ¼cÃÂ¼ler BahÃÂ§esi', 'color' => 'text-primary'],
                    ],
                    'button_text' => 'Anma Kitabesini GÃÂ¶r',
                    'button_url' => '/valiant-shade/pages/news#news-4',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-flower',
                    'order' => 4,
                    'is_active' => true,
                    'views_count' => 1740,
                ],
                [
                    'id' => 5,
                    'title' => 'SimyacÄÂ±lar Ãâ€¡arÅÅ¸ÄÂ±sÄÂ± KaynÄÂ±yor: Sahte DiriliÅÅ¸ ve Mana ÄÂ°ksirleri Piyasada mÄÂ±?',
                    'category' => 'Dedikodu & UyarÄÂ±',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flask',
                    'tag' => 'Ãâ€¡arÅÅ¸ÄÂ± TeftiÅÅ¸i & Skandal',
                    'tag_icon' => 'icon-base ti tabler-search',
                    'date_text' => 'DÃÂ¼n 18:40',
                    'published_date' => '30 EylÃÂ¼l 2026, 18:40',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_arcane_academy.jpg',
                    'location_badge' => 'AÅÅ¸aÄÅ¸ÄÂ± ÅÂehir SimyacÄÂ±lar Ãâ€¡ÄÂ±kmazÄÂ±',
                    'author' => 'Usta SimyacÄÂ± Althea',
                    'author_role' => 'Lonca TeftiÅÅ¸ Heyeti Temsilcisi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/5.png',
                    'is_breaking' => false,
                    'description' => 'Son seferden dÃÂ¶nen maceracÄÂ±larÄÂ±n iksir ÅÅ¸iÅÅ¸elerinde beklenen ÅÅ¸ifa yerine sadece ÅÅ¸ekerli su ve parlayan yosun tozu bulduÄÅ¸u iddiasÄÂ± ÃÂ¼zerine lonca teftiÅÅ¸ mÃÂ¼fettiÅÅ¸leri ÃÂ§arÅÅ¸ÄÂ±daki 3 dÃÂ¼kkanÄÂ± ani baskÄÂ±nla mÃÂ¼hÃÂ¼rledi.',
                    'full_story' => "Son gÃÂ¼nlerde zindan gÃÂ¶revlerinden dÃÂ¶nen savaÅÅ¸ÃÂ§ÄÂ±larÄÂ±n ardÄÂ± ardÄÂ±na saÄÅ¸lÄÂ±k ocaÄÅ¸ÄÂ±na baÅÅ¸vurmasÄÂ± ÃÂ¼zerine korkunÃÂ§ bir skandal patlak verdi. Zindan canavarlarÄÂ±yla ÃÂ§arpÄÂ±ÅÅ¸ÄÂ±rken tÃÂ¼ketilen 'BÃÂ¼yÃÂ¼k Can ÄÂ°ksirleri'nin yaralarÄÂ± iyileÅÅ¸tirmek bir yana, savaÅÅ¸ÃÂ§ÄÂ±larÄÂ±n gÃÂ¶rÃÂ¼ÅÅ¸ÃÂ¼nÃÂ¼ bulanÄÂ±klaÅÅ¸tÄÂ±rdÄÂ±ÄÅ¸ÄÂ± ve mide spazmlarÄÂ±na yol aÃÂ§tÄÂ±ÄÅ¸ÄÂ± ortaya ÃÂ§ÄÂ±ktÄÂ±!

Lonca TeftiÅÅ¸ Heyeti, AÅÅ¸aÄÅ¸ÄÂ± ÅÂehir'deki ÃÂ¼ÃÂ§ simya atÃÂ¶lyesine gece baskÄÂ±nÄÂ± dÃÂ¼zenledi. Ele geÃÂ§irilen yÃÂ¼zlerce ÅÅ¸iÅÅ¸enin mÃÂ¼hÃÂ¼rlerinin sahte olduÄÅ¸u, iÃÂ§eriÄÅ¸inde mandrake kÃÂ¶kÃÂ¼ yerine ucuz bataklÄÂ±k mantarlarÄÂ± ve mavi gÄÂ±da boyasÄÂ± kullanÄÂ±ldÄÂ±ÄÅ¸ÄÂ± tespit edildi.

ÄÂ°ddialara gÃÂ¶re sahtekarlÄÂ±k ÅÅ¸ebekesi, lonca armalÄÂ± kutularÄÂ± kopyalayarak ucuz fiyatla acemi maceracÄÂ±lara pazarlÄÂ±yordu. Sahte iksirleri satan iki tÃÂ¼ccar Lonca ZindanÄÂ±'na kapatÄÂ±ldÄÂ±; lonca hekimleri tÃÂ¼m ÃÂ¼yelere yalnÄÂ±zca Lonca Resmi Eczanesi'nden iksir tedarik etmeleri yÃÂ¶nÃÂ¼nde sert bir uyarÄÂ± yayÄÂ±nladÄÂ±.",
                    'testimony' => 'Can havliyle iksiri kafaya diktiÄÅ¸imde ÅÅ¸ifa yerine aÄÅ¸zÄÂ±mdan mavi kÃÂ¶pÃÂ¼kler ÃÂ§ÄÂ±ktÄÂ±! Neredeyse goblinlerin elinde can verecektim. ââ‚¬â€ SavaÅÅ¸ÃÂ§ÄÂ± Dan',
                    'stats' => [
                        ['label' => 'MÃÂ¼hÃÂ¼rlenen DÃÂ¼kkan', 'value' => '3 AtÃÂ¶lye', 'color' => 'text-danger'],
                        ['label' => 'El Konulan ÅÂiÅÅ¸e', 'value' => '420 Adet', 'color' => 'text-warning'],
                        ['label' => 'Etkilenen ÃÅ“ye', 'value' => '12 MaceracÄÂ±', 'color' => 'text-info'],
                        ['label' => 'Hapis CezasÄÂ±', 'value' => 'MÃÂ¼ebbet Zindan', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Sahte ÄÂ°ksir Listesi',
                    'button_url' => '/valiant-shade/pages/news#news-5',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-alert-circle',
                    'order' => 5,
                    'is_active' => true,
                    'views_count' => 1310,
                ],
                [
                    'id' => 6,
                    'title' => 'Kadim Ejderha Ignis Dize Getirildi: Lonca Hazinesine 14.500 AltÄÂ±n ve Kadim Eserler Eklendi',
                    'category' => 'Sefer Zaferi',
                    'badge_color' => 'success',
                    'category_icon' => 'icon-base ti tabler-trophy',
                    'tag' => 'Efsanevi Zafer',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '3 GÃÂ¼n Ãâ€“nce',
                    'published_date' => '28 EylÃÂ¼l 2026, 11:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_dragon_quest.jpg',
                    'location_badge' => 'Ejderha Zirvesi Harabeleri',
                    'author' => 'General Ragnor',
                    'author_role' => '1. SaldÄÂ±rÄÂ± Lejyonu BaÅÅ¸komutanÄÂ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/6.png',
                    'is_breaking' => false,
                    'description' => '3 hafta sÃÂ¼ren amansÄÂ±z kuÅÅ¸atmanÄÂ±n ardÄÂ±ndan Kadim Alev EjderhasÄÂ± Ignis maÄÅ¸lup edildi. EjderhanÄÂ±n ininden ÃÂ§ÄÂ±karÄÂ±lan Antik Hazine loncamÄÂ±za taÅÅ¸ÄÂ±ndÄÂ±; sefere katÄÂ±lan tÃÂ¼m birliklere yÃÂ¼klÃÂ¼ prim daÄÅ¸ÄÂ±tÄÂ±lÄÂ±yor.',
                    'full_story' => "Valiant Shade'in en tecrÃÂ¼beli 30 elit savaÅÅ¸ÃÂ§ÄÂ±sÄÂ±, haftalardÄÂ±r Ejderha Zirvesi'nin dondurucu zirvelerinde kamp kurarak Kadim Ignis'in inini abluka altÄÂ±nda tutuyordu. DÃÂ¼n sabah ÅÅ¸afak sÃÂ¶kerken baÅÅ¸layan nihai taarruzda, ejderhanÄÂ±n alev menzili bÃÂ¼yÃÂ¼cÃÂ¼lerimizin dondurucu fÄÂ±rtÄÂ±nasÄÂ±yla kÄÂ±rÄÂ±ldÄÂ±.

General Ragnor'un bizzat vurduÄÅ¸u son darbeyle dev yaratÄÂ±k yere serildi. MaÄÅ¸ara derinliklerinden ÃÂ§ÄÂ±karÄÂ±lan ganimetler arasÄÂ±nda; Antik KrallÄÂ±k'tan kalma 14.500 altÄÂ±n sikke, 6 adet efsanevi rÃÂ¼nik silah ve lonca kÃÂ¼tÃÂ¼phanesini aydÄÂ±nlatacak 'GÃÂ¼neÅÅ¸ TaÅÅ¸ÄÂ±' yer alÄÂ±yor.

Zafer kutlamalarÄÂ± bu akÅÅ¸am Lonca Meclis Salonu'nda fÄÂ±ÃÂ§ÄÂ±larÄÂ±n patlatÄÂ±lmasÄÂ±yla baÅÅ¸layacak!",
                    'testimony' => 'KalkanÄÂ±mÄÂ±n ÃÂ¼zerine ateÅÅ¸ pÃÂ¼skÃÂ¼rttÃÂ¼ÄÅ¸ÃÂ¼nde kemiklerimin eridiÄÅ¸ini hissettim ama bir santim bile geri adÄÂ±m atmadÄÂ±k. Bu zafer tÃÂ¼m Valiant Shade ailesinindir! ââ‚¬â€ General Ragnor',
                    'stats' => [
                        ['label' => 'KazanÄÂ±lan AltÄÂ±n', 'value' => '+14.500 GP', 'color' => 'text-warning'],
                        ['label' => 'KazanÄÂ±lan XP', 'value' => '+8.500 XP', 'color' => 'text-success'],
                        ['label' => 'RÃÂ¼nik Eser', 'value' => '6 Kadim Silah', 'color' => 'text-info'],
                        ['label' => 'KayÄÂ±p Durumu', 'value' => '0 Can KaybÄÂ±', 'color' => 'text-success'],
                    ],
                    'button_text' => 'Ganimet PaylaÅÅ¸ÄÂ±mÄÂ±nÄÂ± GÃÂ¶r',
                    'button_url' => '/valiant-shade/pages/news#news-6',
                    'button_color' => 'success',
                    'button_icon' => 'icon-base ti tabler-coin',
                    'order' => 6,
                    'is_active' => true,
                    'views_count' => 3120,
                ],
                [
                    'id' => 7,
                    'title' => "YÃÂ¼ksek Divan KararÄÂ±: 'KÄÂ±zÄÂ±l Maskeli ÄÂ°syancÄÂ±lar' ÄÂ°ÃÂ§in CanlÄÂ± ya da Ãâ€“lÃÂ¼ 5.000 AltÄÂ±n Kelle Ãâ€“dÃÂ¼lÃÂ¼",
                    'category' => 'Lonca Meclisi',
                    'badge_color' => 'primary',
                    'category_icon' => 'icon-base ti tabler-gavel',
                    'tag' => 'Resmi Ferman',
                    'tag_icon' => 'icon-base ti tabler-file-certificate',
                    'date_text' => '4 GÃÂ¼n Ãâ€“nce',
                    'published_date' => '27 EylÃÂ¼l 2026, 16:30',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_hall_council.jpg',
                    'location_badge' => 'Valiant Shade YÃÂ¼ksek Divan Salonu',
                    'author' => 'ÅÂansÃÂ¶lye Valen',
                    'author_role' => 'YÃÂ¼ksek Adalet ve Divan BaÅÅ¸kanÄÂ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/7.png',
                    'is_breaking' => false,
                    'description' => "LoncanÄÂ±n kervan yollarÄÂ±nÄÂ± basÄÂ±p erzak konvoylarÄÂ±nÄÂ± yaÄÅ¸malayan KÄÂ±zÄÂ±l Maske ÃÂ§etesine karÅÅ¸ÄÂ± YÃÂ¼ksek Divan'dan tarihi ferman ÃÂ§ÄÂ±ktÄÂ±: ElebaÅÅ¸ÄÂ± 'Tek GÃÂ¶zlÃÂ¼ Silas' ve kurmaylarÄÂ±nÄÂ±n baÅÅ¸ÄÂ±na rekor kelle ÃÂ¶dÃÂ¼lÃÂ¼ kondu.",
                    'full_story' => "Valiant Shade YÃÂ¼ksek DivanÄÂ±, son 1 ay iÃÂ§inde Kuzey Ticaret Yolu ÃÂ¼zerinde vuku bulan 5 kervan baskÄÂ±nÄÂ±nÄÂ± deÄÅ¸erlendirmek ÃÂ¼zere olaÄÅ¸anÃÂ¼stÃÂ¼ toplandÄÂ±.

Kervan muhafÄÂ±zlarÄÂ±ndan 3 yoldaÅÅ¸ÄÂ±mÄÂ±zÄÂ±n ÅÅ¸ehit olmasÄÂ± ve lonca demirhanesine giden 2 araba dolusu mithril cevherinin ÃÂ§alÄÂ±nmasÄÂ± bardaÄÅ¸ÄÂ± taÅÅ¸ÄÂ±ran son damla oldu. ÅÂansÃÂ¶lye Valen imzalÄÂ± fermana gÃÂ¶re:

1. KÄÂ±zÄÂ±l Maskeli ÃÂ§etenin elebaÅÅ¸ÄÂ± 'Tek GÃÂ¶zlÃÂ¼ Silas'ÄÂ±n kellesine 5.000 AltÄÂ±n,
2. TeÄÅ¸menlerinin her biri iÃÂ§in 1.500 AltÄÂ±n,
3. Ãâ€¡alÄÂ±nan mithril cevherini getiren birliÄÅ¸e cevherin deÄÅ¸erinin %30'u peÅÅ¸in ikramiye ÃÂ¶denecektir.

TÃÂ¼m lonca paralÄÂ± askerleri ve serbest kÄÂ±lÄÂ±ÃÂ§lar bu fermandan yararlanabilir.",
                    'testimony' => 'Ticaret yollarÄÂ±mÄÂ±zÄÂ± tehdit eden hiÃÂ§bir eÅÅ¸kÄÂ±ya cezasÄÂ±z kalmayacak. Adalet ya kÄÂ±lÄÂ±ÃÂ§la ya da iple tecelli edecektir. ââ‚¬â€ ÅÂansÃÂ¶lye Valen',
                    'stats' => [
                        ['label' => 'BaÅÅ¸ Ãâ€“dÃÂ¼lÃÂ¼', 'value' => '5.000 AltÄÂ±n', 'color' => 'text-warning'],
                        ['label' => 'Ãâ€¡ete GÃÂ¼cÃÂ¼', 'value' => '~40 Haydut', 'color' => 'text-danger'],
                        ['label' => 'Operasyon BÃÂ¶lgesi', 'value' => 'Kuzey GeÃÂ§itleri', 'color' => 'text-info'],
                        ['label' => 'Yetki Durumu', 'value' => 'CanlÄÂ± veya Ãâ€“lÃÂ¼', 'color' => 'text-white'],
                    ],
                    'button_text' => 'FermanÄÂ± ÄÂ°mzala & KatÄÂ±l',
                    'button_url' => '/valiant-shade/pages/news#news-7',
                    'button_color' => 'primary',
                    'button_icon' => 'icon-base ti tabler-file-text',
                    'order' => 7,
                    'is_active' => true,
                    'views_count' => 1650,
                ],
                [
                    'id' => 8,
                    'title' => "Obsidyen Hisar'da UyanÄÂ±ÅÅ¸: GÃÂ¶kyÃÂ¼zÃÂ¼nde KÄÂ±rmÄÂ±zÄÂ± Ay ve Ãâ€¡ÄÂ±ÄÅ¸lÄÂ±k Sesleri",
                    'category' => 'Gizem & Kehanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-moon',
                    'tag' => 'KaranlÄÂ±k Kehanet',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '5 GÃÂ¼n Ãâ€“nce',
                    'published_date' => '26 EylÃÂ¼l 2026, 03:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_realm.jpg',
                    'location_badge' => 'Yasak GÃÂ¶lge Vadisi SÄÂ±nÄÂ±rÄÂ±',
                    'author' => 'Kahin Morvath',
                    'author_role' => 'YÄÂ±ldÄÂ±z GÃÂ¶zlemevi BaÅÅ¸ AstroloÄÅ¸u',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/8.png',
                    'is_breaking' => false,
                    'description' => "YÃÂ¼zyÄÂ±llardÄÂ±r mÃÂ¼hÃÂ¼rlÃÂ¼ duran Obsidyen Hisar'ÄÂ±n kulelerinden gÃÂ¶kyÃÂ¼zÃÂ¼ne kan kÄÂ±rmÄÂ±zÄÂ±sÄÂ± bir ÄÂ±ÅÅ¸ÄÂ±k sÃÂ¼tunu yÃÂ¼kseldi. Ãâ€¡evredeki kÃÂ¶ylÃÂ¼ler daÄÅ¸lardan gelen zincir ÅÅ¸akÄÂ±rtÄÂ±larÄÂ± ve ÃÂ§ÄÂ±ÄÅ¸lÄÂ±k sesleri nedeniyle kÃÂ¶ylerini boÅÅ¸altÄÂ±yor.",
                    'full_story' => "GÃÂ¶lge Vadisi sÄÂ±nÄÂ±rÄÂ±ndaki gÃÂ¶zlem kulelerimiz dÃÂ¼n gece dehÅÅ¸et verici doÄÅ¸aÃÂ¼stÃÂ¼ olaylar kaydetti. Gece yarÄÂ±sÄÂ± saat tam 03:00'te ay kÄÂ±rmÄÂ±zÄÂ±ya bÃÂ¼rÃÂ¼ndÃÂ¼ ve Obsidyen Hisar'ÄÂ±n tepe noktasÄÂ±ndan bulutlarÄÂ± delen karanlÄÂ±k bir enerji dalgasÄÂ± yayÄÂ±ldÄÂ±.

GÃÂ¶zlemciler, hisarÄÂ±n ÃÂ§atlaklarÄÂ±ndan akan mor alevleri ve taÅÅ¸ duvarlara ÃÂ§arpan ruhani haykÄÂ±rÄÂ±ÅÅ¸larÄÂ± raporladÄÂ±. Astrologlar heyeti, bu olayÄÂ±n 300 yÄÂ±l ÃÂ¶nce hapsedilen 'GÃÂ¶lge HÃÂ¼kÃÂ¼mdarÄÂ± Malakor'un mÃÂ¼hrÃÂ¼nÃÂ¼n zayÄÂ±fladÄÂ±ÄÅ¸ÄÂ±na dair aÃÂ§ÄÂ±k bir iÅÅ¸aret olduÄÅ¸unu vurguluyor.

Lonca UstasÄÂ±, bÃÂ¶lgeye deneyimli izci birlikleri gÃÂ¶nderilmesi ve sÄÂ±nÄÂ±r karakollarÄÂ±nÄÂ±n tahkim edilmesi talimatÄÂ±nÄÂ± verdi.",
                    'testimony' => 'RÃÂ¼zgar artÄÂ±k daÄÅ¸ kokmuyor, kÃÂ¼kÃÂ¼rt ve bin yÄÂ±llÄÂ±k mezar topraÄÅ¸ÄÂ± kokuyor. MÃÂ¼hÃÂ¼r kÄÂ±rÄÂ±lÄÂ±rsa dÃÂ¼nya karanlÄÂ±ÄÅ¸a boÄÅ¸ulur. ââ‚¬â€ Kahin Morvath',
                    'stats' => [
                        ['label' => 'Tehdit Seviyesi', 'value' => 'Omega SÄÂ±nÄÂ±fÄÂ±', 'color' => 'text-danger'],
                        ['label' => 'GÃÂ¶zlem NoktasÄÂ±', 'value' => 'Kuzey Kulesi', 'color' => 'text-warning'],
                        ['label' => 'Tahliye', 'value' => '2 KÃÂ¶y BoÅÅ¸aldÄÂ±', 'color' => 'text-info'],
                        ['label' => 'MÃÂ¼hÃÂ¼r Durumu', 'value' => 'Kritik Ãâ€¡atlak', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Kehanet ParÅÅ¸ÃÂ¶menini Oku',
                    'button_url' => '/valiant-shade/pages/news#news-8',
                    'button_color' => 'dark',
                    'button_icon' => 'icon-base ti tabler-crystal-ball',
                    'order' => 8,
                    'is_active' => true,
                    'views_count' => 2410,
                ],
            ];
        } else {
            // English default master news dataset
            $rawNews = [
                [
                    'id' => 1,
                    'title' => 'Heavy Losses in Shadow Dungeons Expedition: Head Guard Braum and 4 Scouts Fallen',
                    'category' => 'Death & Fallen',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-skull',
                    'tag' => 'Guild Mourning & Remembrance',
                    'tag_icon' => 'icon-base ti tabler-candle',
                    'date_text' => 'Today 04:15',
                    'published_date' => '01 October 2026, 04:15',
                    'read_time' => '4 min read',
                    'image_file' => 'guild_fallen_memorial.jpg',
                    'location_badge' => 'Shadow Dungeons - 3rd Sealed Tier',
                    'author' => 'Head Scribe Eldrin',
                    'author_role' => 'Senior Clerk of the High Council',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/1.png',
                    'is_breaking' => true,
                    'description' => "Ambushed by Ancient Lich Malgok on the 3rd tier of Shadow Dungeons, 1st Reconnaissance Unit lost Head Guard Braum 'Ironheart' and 4 brave young warriors. Official 3-day guild mourning declared.",
                    'full_story' => "According to the solemn statement issued at dawn by the Valiant Shade High Command; the 'Silver Talon' patrol unit descended into the sealed 3rd level of the Shadow Dungeons for routine mapping, falling into an unmapped necromantic ambush.

Leading the company, 28-year veteran Head Guard Braum 'Ironheart' formed a solitary bulwark beneath a crumbling marble archway to allow wounded scouts and apprentice mages to retreat. His valiant companions Ranger Kaelen, Knight Varis, Apprentice Arcanist Alistair, and Medic Lyanna held their ground until their final breath.

Surviving scouts attest that Braum hammered his shield into the dungeon threshold, securing the safe evacuation of 8 guild members. Their remains were escorted to the Council Chapel by the Guild Knights at first light.

By decree of Guild Master Lord Gerald: Valiant Shade banners are lowered to half-mast, a memorial altar has been erected in the great hall, and lifetime pensions are granted from the Guild Vault to the martyrs' kin. May they rest in the Ancient Light.",
                    'testimony' => "With his dying breath, Braum slammed his shield down and roared: 'Stop covering my back, get the apprentices out!' Because of him, we breathe today. ââ‚¬â€ Ranger Finn, Survivor",
                    'stats' => [
                        ['label' => 'Fallen Count', 'value' => '5 Heroes', 'color' => 'text-danger'],
                        ['label' => 'Lives Saved', 'value' => '8 Scouts', 'color' => 'text-success'],
                        ['label' => 'Guild Status', 'value' => '3-Day Mourning', 'color' => 'text-warning'],
                        ['label' => 'Danger Code', 'value' => 'Code Red (Lich)', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Condolence Book & Details',
                    'button_url' => '/valiant-shade/pages/news#news-1',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-candle',
                    'order' => 1,
                    'is_active' => true,
                    'views_count' => 1420,
                ],
                [
                    'id' => 2,
                    'title' => 'Red Tavern Whispers: Secret Midnight Rendezvous of a Guild Executive with the Shadow Society!',
                    'category' => 'Gossip & Scandal',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flame',
                    'tag' => 'Tavern Gossip',
                    'tag_icon' => 'icon-base ti tabler-message-2-question',
                    'date_text' => 'Last Night',
                    'published_date' => '30 September 2026, 23:45',
                    'read_time' => '3 min read',
                    'image_file' => 'guild_tavern_whispers.jpg',
                    'location_badge' => 'Red Tavern - Back Booth No: 4',
                    'author' => 'Tavern Keeper Barnaby',
                    'author_role' => 'Head Operator of Red Tavern & City Watcher',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/2.png',
                    'is_breaking' => false,
                    'description' => 'Whispers broke out in the dim back corners of Red Tavern: a hooded executive from Valiant Shade High Council allegedly shook hands secretly with a forbidden Shadow Thieves Guild emissary for dark gold and a blood-sealed pact!',
                    'full_story' => "Whispers circulated through the dim corners behind the ale barrels at Red Tavern last night, causing even the stoutest dwarves to pause in their drinking!

At three quarters past midnight, a tall figure in a grey cloak sporting the golden sash of Valiant Shade slipped through the service door. Waiting in booth 4 was the 'Shadow Emissary' wearing a silver fox mask, known from the city's murky underworld.

According to dwarf server Thorbjorn, the purse set upon the table spilled not ordinary imperial gold, but minted 'Dark Emerald' bullion! The pair signed a parchment smoldering with blue sorcerous flame before vanishing into the city sewers.

While the High Council has not confirmed the rumors, whispers link this meeting with the missing 'Obsidian Vault Key' unaccounted for these past two weeks.",
                    'testimony' => "I saw the clasp beneath the cloak with my own eyesââ‚¬â€the eagle crest of the High Council. They didn't even clink mugs; took the coin and dissolved into the dark. ââ‚¬â€ Dwarf Thorbjorn",
                    'stats' => [
                        ['label' => 'Credibility', 'value' => '75% Allegation', 'color' => 'text-warning'],
                        ['label' => 'Eyewitness', 'value' => '3 Tables', 'color' => 'text-info'],
                        ['label' => 'Suspect Document', 'value' => 'Blood Sealed', 'color' => 'text-danger'],
                        ['label' => 'Bribe Estimate', 'value' => '5,000 Dark Coins', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'Investigate Whispers',
                    'button_url' => '/valiant-shade/pages/news#news-2',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-eye',
                    'order' => 2,
                    'is_active' => true,
                    'views_count' => 2180,
                ],
                [
                    'id' => 3,
                    'title' => 'Midnight Assassination Attempt on Guild Treasurer: Poisoned Arrow and a Leftover Black Letter',
                    'category' => 'Assassination & Treason',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-dagger',
                    'tag' => 'Security Alert',
                    'tag_icon' => 'icon-base ti tabler-alert-triangle',
                    'date_text' => '12 hours ago',
                    'published_date' => '01 October 2026, 10:30',
                    'read_time' => '3 min read',
                    'image_file' => 'guild_shadow_conspiracy.jpg',
                    'location_badge' => 'Treasury Tower - Guard Courtyard',
                    'author' => 'Guard Captain Torin',
                    'author_role' => 'Chief of Castle Security & Peacekeeping',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/3.png',
                    'is_breaking' => false,
                    'description' => "Guild Treasurer Lord Vance was targeted by a poisoned crossbow bolt that pierced his window while reviewing budget records! While Vance escaped by a hair, the attacker left a scroll stating 'The vault will open, the price will be paid.'",
                    'full_story' => "The Treasury Chambers witnessed a bloody assassination attempt last night. Around 01:20, a shadowy crossbow bolt fired from the east tower passed inches from Treasurer Lord Vance's ear, burying itself deep in an oak bookcase.

Alchemists confirmed the bolt was coated in rare 'Wyvern Breath' neurotoxin, capable of stopping a heart within seconds from a mere scratch.

A dropped silk handkerchief bore the embossed seal of rival syndicates and a grim message: 'The vault will open, the price paid in blood.'

Captain Torin placed the castle on red alert, restricting access to the Treasury Tower.",
                    'testimony' => 'Green smoke hissed from the wood where the bolt struck. The target was my throat. There will be no peace until the traitor within is exposed. ââ‚¬â€ Lord Vance',
                    'stats' => [
                        ['label' => 'Target', 'value' => 'Lord Vance', 'color' => 'text-white'],
                        ['label' => 'Poison Used', 'value' => 'Wyvern Breath', 'color' => 'text-danger'],
                        ['label' => 'Alert Level', 'value' => 'Code Red', 'color' => 'text-danger'],
                        ['label' => 'Bounty', 'value' => '2,500 Gold', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'Assassination Report',
                    'button_url' => '/valiant-shade/pages/news#news-3',
                    'button_color' => 'dark',
                    'button_icon' => 'icon-base ti tabler-shield-alert',
                    'order' => 3,
                    'is_active' => true,
                    'views_count' => 1890,
                ],
                [
                    'id' => 4,
                    'title' => 'Solemn Funeral Ceremony in the Great Chapel for Archmage Lyra, Martyr of Dragon Pass',
                    'category' => 'Death & Memorial',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-flame-off',
                    'tag' => 'Chapel Ceremony & Farewell',
                    'tag_icon' => 'icon-base ti tabler-ribbon-health',
                    'date_text' => '2 Days Ago',
                    'published_date' => '29 September 2026, 14:00',
                    'read_time' => '3 min read',
                    'image_file' => 'guild_heroes_funeral.jpg',
                    'location_badge' => 'Sky Temple & Martyrs Square',
                    'author' => 'High Patriarch Aurelius',
                    'author_role' => 'High Priest of Sun Chapel',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/4.png',
                    'is_breaking' => false,
                    'description' => 'Archmage Lyra Stormchaser, who shielded her unit against a flaming vortex during the Dragon Pass Expedition, was laid to eternal rest with a grand ceremony attended by hundreds of guild members and citizens.',
                    'full_story' => "Streets were draped with black pennants and white lilies from early dawn. It was a day of mourning for Archmage Lyra Stormchaser, who conjured a 'Starlight Aegis' against the Red Dragon's inferno, giving her life to save 14 young guild members.

In a solemn procession from the Sky Temple to Martyrs Square, 12 senior paladins carried Lyra's staff and vestments. The Guild Master posthumously awarded her the 'Valiant Shade Medal of Valor'.",
                    'testimony' => "As the flames rushed down, she didn't yield an inch. 'Fly!' she yelled, 'I will hold the light'. That light shall never fade. ââ‚¬â€ Apprentice Mage Selene",
                    'stats' => [
                        ['label' => 'Lives Saved', 'value' => '14 Members', 'color' => 'text-success'],
                        ['label' => 'Attendance', 'value' => '800+ People', 'color' => 'text-info'],
                        ['label' => 'Merit Badge', 'value' => 'Gold Medal', 'color' => 'text-warning'],
                        ['label' => 'Memorial Tomb', 'value' => 'Garden of Mages', 'color' => 'text-primary'],
                    ],
                    'button_text' => 'View Memorial Tablet',
                    'button_url' => '/valiant-shade/pages/news#news-4',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-flower',
                    'order' => 4,
                    'is_active' => true,
                    'views_count' => 1740,
                ],
                [
                    'id' => 5,
                    'title' => 'Alchemists Market in Turmoil: Fake Resurrection and Mana Potions on the Market?',
                    'category' => 'Gossip & Warning',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flask',
                    'tag' => 'Market Inspection & Scandal',
                    'tag_icon' => 'icon-base ti tabler-search',
                    'date_text' => 'Yesterday 18:40',
                    'published_date' => '30 September 2026, 18:40',
                    'read_time' => '2 min read',
                    'image_file' => 'guild_arcane_academy.jpg',
                    'location_badge' => 'Lower City Alchemists Alley',
                    'author' => 'Master Alchemist Althea',
                    'author_role' => 'Guild Inspection Representative',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/5.png',
                    'is_breaking' => false,
                    'description' => 'Guild inspection officers raided and sealed 3 shops in the bazaar after returning adventurers discovered their potion vials contained only sugar water and glowing moss dust instead of healing elixir.',
                    'full_story' => "A scandal erupted when multiple frontline fighters reported to the infirmary after drinking 'Greater Healing Draughts' that caused blurred vision and nausea instead of closing wounds.

Inspectors raided three Lower City ateliers, confiscating hundreds of counter-marked bottles filled with swamp lichen extract and food dye. Two illicit merchants have been imprisoned.",
                    'testimony' => 'In dire peril I quaffed the flask, and blue froth erupted from my mouth instead of mend! I nearly fell to goblins. ââ‚¬â€ Dan the Fighter',
                    'stats' => [
                        ['label' => 'Sealed Shops', 'value' => '3 Ateliers', 'color' => 'text-danger'],
                        ['label' => 'Confiscated Vials', 'value' => '420 Units', 'color' => 'text-warning'],
                        ['label' => 'Affected Members', 'value' => '12 Adventurers', 'color' => 'text-info'],
                        ['label' => 'Prison Sentence', 'value' => 'Life Dungeon', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Fake Potion List',
                    'button_url' => '/valiant-shade/pages/news#news-5',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-alert-circle',
                    'order' => 5,
                    'is_active' => true,
                    'views_count' => 1310,
                ],
                [
                    'id' => 6,
                    'title' => 'Ancient Dragon Ignis Vanquished: 14,500 Gold and Ancient Relics Added to Guild Vault',
                    'category' => 'Expedition Victory',
                    'badge_color' => 'success',
                    'category_icon' => 'icon-base ti tabler-trophy',
                    'tag' => 'Legendary Victory',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '3 Days Ago',
                    'published_date' => '28 September 2026, 11:00',
                    'read_time' => '3 min read',
                    'image_file' => 'guild_dragon_quest.jpg',
                    'location_badge' => 'Dragon Peak Ruins',
                    'author' => 'General Ragnor',
                    'author_role' => 'Commander-in-Chief of 1st Assault Legion',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/6.png',
                    'is_breaking' => false,
                    'description' => 'After a 3-week relentless siege, Ancient Flame Dragon Ignis was defeated. The ancient treasure retrieved from the dragon lair was transferred to our guild; handsome bonuses are distributed to all units.',
                    'full_story' => "Thirty of Valiant Shade's elite combatants blockaded the icy spires of Dragon Peak for weeks. At dawn yesterday, the final assault breached the fire drake's domain.

General Ragnor dealt the final blow. Recovered treasures include 14,500 ancient minted gold coins, 6 runic armaments, and the legendary 'Sun Stone' to illuminate the grand library.",
                    'testimony' => 'When fire washed across my shield my marrow burned, yet we gave not an inch. This victory belongs to all Valiant Shade! ââ‚¬â€ General Ragnor',
                    'stats' => [
                        ['label' => 'Gold Won', 'value' => '+14,500 GP', 'color' => 'text-warning'],
                        ['label' => 'XP Earned', 'value' => '+8,500 XP', 'color' => 'text-success'],
                        ['label' => 'Runic Relics', 'value' => '6 Weapons', 'color' => 'text-info'],
                        ['label' => 'Casualties', 'value' => '0 Losses', 'color' => 'text-success'],
                    ],
                    'button_text' => 'View Loot Split',
                    'button_url' => '/valiant-shade/pages/news#news-6',
                    'button_color' => 'success',
                    'button_icon' => 'icon-base ti tabler-coin',
                    'order' => 6,
                    'is_active' => true,
                    'views_count' => 3120,
                ],
                [
                    'id' => 7,
                    'title' => "High Council Decree: 5,000 Gold Bounty Dead or Alive for 'Red Masked Rebels'",
                    'category' => 'Guild Council',
                    'badge_color' => 'primary',
                    'category_icon' => 'icon-base ti tabler-gavel',
                    'tag' => 'Official Decree',
                    'tag_icon' => 'icon-base ti tabler-file-certificate',
                    'date_text' => '4 Days Ago',
                    'published_date' => '27 September 2026, 16:30',
                    'read_time' => '2 min read',
                    'image_file' => 'guild_hall_council.jpg',
                    'location_badge' => 'Valiant Shade High Council Chamber',
                    'author' => 'Chancellor Valen',
                    'author_role' => 'President of High Justice & Council',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/7.png',
                    'is_breaking' => false,
                    'description' => "A historic decree was issued by the High Council against the Red Mask gang who raided caravan routes: a record bounty was placed on leader 'One-Eyed Silas' and his staff.",
                    'full_story' => "The High Council convened in emergency session following 5 caravan raids on the Northern Trade Route.

Three escorts fell and two wagons of mithril ore were looted. Chancellor Valen decreed:

1. 5,000 Gold for Silas' head,
2. 1,500 Gold for each lieutenant,
3. 30% bonus on the spot for recovered mithril.

All free blades and guild mercenaries are eligible.",
                    'testimony' => 'No outlaw menacing our lifelines shall evade retribution. Justice shall be done by blade or noose. ââ‚¬â€ Chancellor Valen',
                    'stats' => [
                        ['label' => 'Bounty Head', 'value' => '5,000 Gold', 'color' => 'text-warning'],
                        ['label' => 'Gang Strength', 'value' => '~40 Bandits', 'color' => 'text-danger'],
                        ['label' => 'Operation Area', 'value' => 'North Passes', 'color' => 'text-info'],
                        ['label' => 'Authority', 'value' => 'Dead or Alive', 'color' => 'text-white'],
                    ],
                    'button_text' => 'Sign Decree & Join',
                    'button_url' => '/valiant-shade/pages/news#news-7',
                    'button_color' => 'primary',
                    'button_icon' => 'icon-base ti tabler-file-text',
                    'order' => 7,
                    'is_active' => true,
                    'views_count' => 1650,
                ],
                [
                    'id' => 8,
                    'title' => 'Awakening at Obsidian Citadel: Red Moon and Screams in the Night Sky',
                    'category' => 'Mystery & Prophecy',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-moon',
                    'tag' => 'Dark Prophecy',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '5 Days Ago',
                    'published_date' => '26 September 2026, 03:00',
                    'read_time' => '3 min read',
                    'image_file' => 'guild_shadow_realm.jpg',
                    'location_badge' => 'Forbidden Shadow Valley Border',
                    'author' => 'Prophet Morvath',
                    'author_role' => 'Chief Astrologer of Star Observatory',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/8.png',
                    'is_breaking' => false,
                    'description' => 'A blood-red pillar of light rose into the sky from the towers of the Obsidian Citadel, sealed for centuries. Surrounding villagers are evacuating their villages due to rattling chains and shrieks echoing from the mountains.',
                    'full_story' => 'Border sentries recorded supernatural activity at midnight: the moon turned blood red and an energy pulse pierced the sky from the Obsidian Citadel.

Observers saw purple flames and ghostly shrieks beating upon stone walls. Astrologers warn that the seal binding Malakor the Shadow Lord is fracturing.',
                    'testimony' => 'The wind no longer smells of stone, but of brimstone and centuries of sepulchral earth. If the seal shatters, darkness consumes all. ââ‚¬â€ Prophet Morvath',
                    'stats' => [
                        ['label' => 'Threat Level', 'value' => 'Omega Class', 'color' => 'text-danger'],
                        ['label' => 'Observation Post', 'value' => 'North Tower', 'color' => 'text-warning'],
                        ['label' => 'Evacuation', 'value' => '2 Villages Empty', 'color' => 'text-info'],
                        ['label' => 'Seal Status', 'value' => 'Critical Fracture', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Read Prophecy Scroll',
                    'button_url' => '/valiant-shade/pages/news#news-8',
                    'button_color' => 'dark',
                    'button_icon' => 'icon-base ti tabler-crystal-ball',
                    'order' => 8,
                    'is_active' => true,
                    'views_count' => 2410,
                ],
            ];
        }

        return collect($rawNews)->map(function ($item) {
            $imageFile = $item['image_file'] ?? 'guild_dragon_quest.jpg';
            $item['image_url'] = 'static/valiant-shade/assets/img/news/'.$imageFile;
            $item['image_asset'] = asset($item['image_url']);

            return (object) $item;
        });
    }

    /**
     * Retrieve active news items ordered for display.
     */
    public static function getActiveNews(): Collection
    {
        $dbNews = News::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->orderBy('order')
            ->get();
        if ($dbNews->isNotEmpty()) {
            return $dbNews;
        }

        return self::getNewsData()
            ->where('is_active', true)
            ->sortBy('order')
            ->values();
    }

    /**
     * Display the rich Guild News & Chronicles reader page.
     */
    public function index(Request $request): View
    {
        $pendingNews = News::query()
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $dbNews = News::query()
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $allNews = $dbNews->isNotEmpty() ? $dbNews : self::getNewsData();

        // Optional category filtering
        $activeCategory = $request->query('category', 'all');
        $searchQuery = trim((string) $request->query('q', ''));

        $filteredNews = $allNews;

        $categoryMap = [
            'casualties' => [1, 6],
            'council' => [2, 7],
            'expedition' => [3, 5],
            'rumors' => [4],
            'assassination' => [7],
        ];

        if ($activeCategory !== 'all') {
            if (isset($categoryMap[$activeCategory])) {
                $allowedIds = $categoryMap[$activeCategory];
                $filteredNews = $filteredNews->filter(fn ($item) => in_array((int) $item->id, $allowedIds, true));
            } else {
                $filteredNews = $filteredNews->filter(function ($item) use ($activeCategory) {
                    return stripos($item->category, $activeCategory) !== false;
                });
            }
        }

        if (! empty($searchQuery)) {
            $filteredNews = $filteredNews->filter(function ($item) use ($searchQuery) {
                return stripos($item->title, $searchQuery) !== false
                    || stripos($item->description, $searchQuery) !== false
                    || stripos($item->full_story, $searchQuery) !== false
                    || stripos($item->location_badge, $searchQuery) !== false
                    || stripos($item->author, $searchQuery) !== false;
            });
        }

        // Highlights & Counts (Language-neutral ID fallback + multi-locale category keywords)
        $featuredNews = $allNews->firstWhere('is_breaking', true) ?? $allNews->first();
        $deathNewsCount = $allNews->filter(fn ($n) => in_array((int) $n->id, [1, 6], true) || stripos($n->category, 'Ãâ€“lÃÂ¼m') !== false || stripos($n->category, 'Death') !== false)->count();
        $rumorNewsCount = $allNews->filter(fn ($n) => in_array((int) $n->id, [4], true) || stripos($n->category, 'Dedikodu') !== false || stripos($n->category, 'Rumor') !== false || stripos($n->category, 'Gossip') !== false)->count();
        $victoryNewsCount = $allNews->filter(fn ($n) => in_array((int) $n->id, [3, 5], true) || stripos($n->category, 'Zafer') !== false || stripos($n->category, 'Victory') !== false || stripos($n->category, 'Expedition') !== false)->count();
        $decreeNewsCount = $allNews->filter(fn ($n) => in_array((int) $n->id, [2, 7], true) || stripos($n->category, 'Meclis') !== false || stripos($n->category, 'Ferman') !== false || stripos($n->category, 'Decree') !== false || stripos($n->category, 'Council') !== false)->count();

        $statsSummary = [
            'total' => $allNews->count(),
            'deaths' => $deathNewsCount,
            'rumors' => $rumorNewsCount,
            'victories' => $victoryNewsCount,
            'decrees' => $decreeNewsCount,
        ];

        $gazetteSlotCounts = [
            'all' => $allNews->count(),
            'lead' => 0,
            'corner' => 0,
            'article' => 0,
            'flash' => 0,
        ];
        foreach ($allNews as $item) {
            $stats = is_object($item) ? ($item->stats ?? null) : ($item['stats'] ?? null);
            $kind = NewsStats::gazetteSlotKind(is_array($stats) ? $stats : null);
            if (! isset($gazetteSlotCounts[$kind])) {
                $gazetteSlotCounts[$kind] = 0;
            }
            $gazetteSlotCounts[$kind]++;
        }

        $management = $this->newsManagementFlags();

        return view('valiant-shade.content.pages.pages-news', [
            'news' => $filteredNews->values(),
            'allNews' => $allNews,
            'allNewsJson' => $this->serializeNewsForClient(
                $allNews->concat($pendingNews)->unique(fn ($item) => (int) (is_object($item) ? $item->id : ($item['id'] ?? 0)))
            ),
            'pendingNews' => $pendingNews,
            'featuredNews' => $featuredNews,
            'statsSummary' => $statsSummary,
            'gazetteSlotCounts' => $gazetteSlotCounts,
            'activeCategory' => $activeCategory,
            'searchQuery' => $searchQuery,
            'usesDatabaseNews' => $dbNews->isNotEmpty(),
            ...$management,
        ]);
    }

    /**
     * @return array{canManageNews: bool, canUpdateNews: bool, canDeleteNews: bool, canApproveNews: bool}
     */
    protected function newsManagementFlags(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'canManageNews' => false,
                'canUpdateNews' => false,
                'canDeleteNews' => false,
                'canApproveNews' => false,
            ];
        }

        if ($user->isAdmin() || $user->hasPermission('archives.all') || $user->isArchivistBailiff()) {
            return [
                'canManageNews' => true,
                'canUpdateNews' => true,
                'canDeleteNews' => true,
                'canApproveNews' => $user->isAdmin(),
            ];
        }

        $canUpdate = $user->hasPermission('archives.update') || $user->hasPermission('archives.create');
        $canDelete = $user->hasPermission('archives.delete');

        return [
            'canManageNews' => $canUpdate || $canDelete,
            'canUpdateNews' => $canUpdate,
            'canDeleteNews' => $canDelete,
            'canApproveNews' => false,
        ];
    }

    protected function serializeNewsForClient(Collection $allNews): Collection
    {
        $databaseIds = News::query()->pluck('id')->all();

        return $allNews->map(function ($item) use ($databaseIds) {
            if ($item instanceof News) {
                $row = $item->toArray();
                $row['image_asset'] = $item->image_asset;
                $row['is_database'] = true;
                $row['gazette_slot_kind'] = NewsStats::gazetteSlotKind(is_array($item->stats) ? $item->stats : null);
                $row['gazette_slot_badge'] = NewsStats::submissionBadge(is_array($item->stats) ? $item->stats : null);
                $row['reaction_metrics'] = NewsEngagement::reactionMetrics(is_array($item->stats) ? $item->stats : null);
                $row['engagement_metrics'] = NewsEngagement::metrics(is_array($item->stats) ? $item->stats : null);

                return $row;
            }

            $row = (array) $item;
            if (! isset($row['image_asset']) && ! empty($row['image_url'])) {
                $row['image_asset'] = asset(ltrim((string) $row['image_url'], '/'));
            }
            $row['is_database'] = in_array((int) ($row['id'] ?? 0), $databaseIds, true);
            $row['gazette_slot_kind'] = NewsStats::gazetteSlotKind(is_array($row['stats'] ?? null) ? $row['stats'] : null);
            $row['gazette_slot_badge'] = NewsStats::submissionBadge(is_array($row['stats'] ?? null) ? $row['stats'] : null);
            $row['reaction_metrics'] = NewsEngagement::reactionMetrics(is_array($row['stats'] ?? null) ? $row['stats'] : null);
            $row['engagement_metrics'] = NewsEngagement::metrics(is_array($row['stats'] ?? null) ? $row['stats'] : null);

            return $row;
        })->values();
    }

    protected function authorizeNewsManagement(string $action = 'delete'): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->isAdmin() || $user->hasPermission('archives.all') || $user->isArchivistBailiff()) {
            return;
        }

        $permission = match ($action) {
            'delete' => 'archives.delete',
            'update' => 'archives.update',
            'create' => 'archives.create',
            default => 'archives.all',
        };
        abort_unless($user->hasPermission($permission), 403, __('Access denied. You do not have permission to manage news.'));
    }

    /**
     * Show single news item as JSON (for detail reader modal / API).
     */
    public function show(int $id, Request $request): JsonResponse|View
    {
        $newsItem = News::query()->where('status', 'published')->find($id);
        if (! $newsItem) {
            $newsItem = self::getNewsData()->firstWhere('id', $id);
        }

        if (! $newsItem) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => __('News not found.')], 404);
            }

            return redirect()->route('valiant-shade.pages-news')->with('error', __('News not found.'));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'news' => $newsItem,
            ]);
        }

        return view('valiant-shade.content.pages.pages-news', [
            'news' => self::getNewsData(),
            'allNews' => self::getNewsData(),
            'featuredNews' => $newsItem,
            'selectedNews' => $newsItem,
            'statsSummary' => [
                'total' => 8,
                'deaths' => 2,
                'rumors' => 2,
                'victories' => 1,
                'decrees' => 1,
            ],
            'activeCategory' => 'all',
            'searchQuery' => '',
        ]);
    }

    /**
     * Store new news record.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeNewsManagement('create');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'badge_color' => 'nullable|string|max:50',
            'category_icon' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:100',
            'tag_icon' => 'nullable|string|max:100',
            'date_text' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image_preset' => 'nullable|string',
            'image_url' => 'nullable|string',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'button_color' => 'nullable|string|max:50',
            'button_icon' => 'nullable|string|max:100',
            'location_badge' => 'nullable|string|max:150',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
            'stats' => 'nullable|array',
            'stats.*.label' => 'nullable|string|max:50',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.color' => 'nullable|string|max:50',
        ]);

        $autoImage = ! $request->hasFile('custom_image')
            && (empty($request->image_preset) || $request->image_preset === '__gazette_auto__');
        $imageUrl = $validated['image_url']
            ?? ($autoImage ? 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg' : ($validated['image_preset'] ?? 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg'));
        if ($request->hasFile('custom_image')) {
            $file = $request->file('custom_image');
            $filename = 'news_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (! file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/'.$filename;
        }

        $news = News::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'badge_color' => $validated['badge_color'] ?? 'primary',
            'category_icon' => $validated['category_icon'] ?? 'icon-base ti tabler-trophy',
            'tag' => $validated['tag'] ?? null,
            'tag_icon' => $validated['tag_icon'] ?? 'icon-base ti tabler-sparkles',
            'date_text' => $validated['date_text'] ?? null,
            'description' => $validated['description'] ?? '',
            'image_url' => $imageUrl,
            'stats' => $validated['stats'] ?? [],
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'button_color' => $validated['button_color'] ?? 'primary',
            'button_icon' => $validated['button_icon'] ?? 'icon-base ti tabler-shield-check',
            'location_badge' => $validated['location_badge'] ?? null,
            'order' => $validated['order'] ?? (News::max('order') + 1),
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
            'status' => 'published',
        ]);

        if ($autoImage) {
            GazetteNewsImage::assignFromContent($news);
            $news->saveQuietly();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('News added successfully.'),
                'news' => $news,
            ]);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('success', __('News added successfully.'));
    }

    /**
     * Update existing news record.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeNewsManagement('update');

        $news = News::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'badge_color' => 'nullable|string|max:50',
            'category_icon' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:100',
            'tag_icon' => 'nullable|string|max:100',
            'date_text' => 'nullable|string|max:100',
            'description' => 'required|string',
            'image_preset' => 'nullable|string',
            'image_url' => 'nullable|string',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'button_color' => 'nullable|string|max:50',
            'button_icon' => 'nullable|string|max:100',
            'location_badge' => 'nullable|string|max:150',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
            'stats' => 'nullable|array',
            'stats.*.label' => 'nullable|string|max:50',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.color' => 'nullable|string|max:50',
        ]);

        $autoImageSelected = ! $request->hasFile('custom_image')
            && ($validated['image_preset'] ?? null) === '__gazette_auto__';

        $imageUrl = $news->image_url;
        if ($request->hasFile('custom_image')) {
            $file = $request->file('custom_image');
            $filename = 'news_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (! file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/'.$filename;
        } elseif (! empty($validated['image_preset']) && $validated['image_preset'] !== '__gazette_auto__') {
            $imageUrl = $validated['image_preset'];
        } elseif (! empty($validated['image_url'])) {
            $imageUrl = $validated['image_url'];
        }

        $news->fill([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'badge_color' => $validated['badge_color'] ?? $news->badge_color,
            'category_icon' => $validated['category_icon'] ?? $news->category_icon,
            'tag' => $validated['tag'] ?? null,
            'tag_icon' => $validated['tag_icon'] ?? $news->tag_icon,
            'date_text' => $validated['date_text'] ?? null,
            'description' => $validated['description'],
            'stats' => $validated['stats'] ?? $news->stats,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'button_color' => $validated['button_color'] ?? $news->button_color,
            'button_icon' => $validated['button_icon'] ?? $news->button_icon,
            'location_badge' => $validated['location_badge'] ?? null,
            'order' => $validated['order'] ?? $news->order,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : $news->is_active,
        ]);

        if (! $autoImageSelected) {
            $news->image_url = $imageUrl;
        }

        $news->save();

        if ($autoImageSelected) {
            GazetteNewsImage::assignFromContent($news);
            $news->saveQuietly();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('News updated successfully.'),
                'news' => $news,
            ]);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('success', __('News updated successfully.'));
    }

    /**
     * Drag-drop sÄÂ±ralama: ÃÂ¼stteki kart en yÃÂ¼ksek order deÄÅ¸erini alÄÂ±r (gazete iÃÂ§ sayfa sÄÂ±rasÄÂ±).
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorizeNewsManagement('update');

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|distinct',
        ]);

        $ids = array_values(array_map('intval', $data['ids']));
        $count = count($ids);
        foreach ($ids as $index => $id) {
            News::query()->where('id', $id)->update(['order' => $count - $index]);
        }

        app(PublicNewsService::class)->invalidatePublicFeed();

        return response()->json([
            'success' => true,
            'message' => __('Order saved.'),
        ]);
    }

    /**
     * Delete news record.
     */
    public function destroy(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeNewsManagement('delete');

        $news = News::findOrFail($id);
        $gazette = app(GazetteIssueService::class);
        $gazette->releaseNewsFromGazette($news);
        $news->delete();
        $gazette->afterExtraIssueNewsRemoved($news);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => __('News deleted.')]);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('success', __('News deleted.'));
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $news = News::findOrFail($id);
        if ($blocked = $this->blockedIfGazetteIssueLocked($news, $request)) {
            return $blocked;
        }
        $news->is_active = ! $news->is_active;
        $news->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'is_active' => (bool) $news->is_active, 'message' => __('Status updated.')]);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('success', __('Status updated.'));
    }

    /**
     * Approve a pending rumor / submission for public display.
     */
    public function approve(int $id, Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403, __('Only administrators can publish news.'));

        $news = News::findOrFail($id);
        if ($blocked = $this->blockedIfGazetteIssueLocked($news, $request)) {
            return $blocked;
        }
        $news->update([
            'status' => 'published',
            'is_active' => true,
            'date_text' => $news->date_text ?: now()->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
        ]);

        $news->refresh();
        app(GazetteIssueService::class)->applySubmissionPlacement($news);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => __('News published.'), 'news' => $news]);
        }

        return redirect()->route('valiant-shade.pages-news')->with('success', __('News published.'));
    }

    /**
     * Reject a pending submission.
     */
    public function reject(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $news = News::findOrFail($id);
        app(GazetteIssueService::class)->releaseNewsFromGazette($news);
        $news->update([
            'status' => 'rejected',
            'is_active' => false,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => __('Submission rejected.'), 'news' => $news]);
        }

        return redirect()->route('valiant-shade.pages-news')->with('success', __('Submission rejected.'));
    }

    /**
     * Reset default news bulletins.
     */
    public function resetDefaults(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeNewsManagement('update');

        News::truncate();
        if (class_exists(NewsSeeder::class)) {
            $seeder = new NewsSeeder;
            $seeder->run();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => __('Default bulletins restored.')]);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('success', __('Default bulletins restored.'));
    }

    protected function blockedIfGazetteIssueLocked(News $news, Request $request): RedirectResponse|JsonResponse|null
    {
        $issueId = (int) ($news->gazette_issue_id ?? 0);
        if ($issueId <= 0) {
            return null;
        }

        if (! app(GazetteIssueService::class)->issueIsLocked($issueId)) {
            return null;
        }

        $message = 'Bu gazete sayÄÂ±sÄÂ± kilitli; haber durumu deÄÅ¸iÅÅ¸tirilemez.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('error', $message);
    }

    public function editorialReaction(Request $request, int $id): JsonResponse
    {
        $this->authorizeNewsManagement('update');

        $validated = $request->validate([
            'reaction' => 'required|string|in:victory,mourning,whisper,bounty',
        ]);

        $news = News::query()->findOrFail($id);
        $labels = NewsEngagement::reactionLabels();

        NewsEngagement::recordEditorialReaction(
            $news,
            $validated['reaction'],
            $labels[$validated['reaction']],
        );

        app(PublicNewsService::class)->invalidatePublicFeed();

        return response()->json([
            'success' => true,
            'message' => __('Editorial reaction recorded.'),
            'stats' => $news->fresh()->stats,
        ]);
    }
}

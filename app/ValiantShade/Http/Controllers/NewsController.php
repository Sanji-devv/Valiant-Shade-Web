<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\News;
use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\GazetteIssueService;
use App\ValiantShade\Services\PublicNewsService;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Support\NewsEngagement;
use App\ValiantShade\Support\NewsStats;
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
                    'title' => 'GÃ¶lge Dehlizleri Seferinde AÄŸÄ±r KayÄ±p: BaÅŸ MuhafÄ±z Braum ve 4 KeÅŸif Eri Åehit DÃ¼ÅŸtÃ¼',
                    'category' => 'Ã–lÃ¼m & Åehitler',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-skull',
                    'tag' => 'Lonca YasÄ± & Anma',
                    'tag_icon' => 'icon-base ti tabler-candle',
                    'date_text' => 'BugÃ¼n 04:15',
                    'published_date' => '01 Ekim 2026, 04:15',
                    'read_time' => '4 dk okuma',
                    'image_file' => 'guild_fallen_memorial.jpg',
                    'location_badge' => 'GÃ¶lge Dehlizleri - 3. MÃ¼hÃ¼rlÃ¼ Kat',
                    'author' => 'Lonca BaÅŸkÃ¢tibi Eldrin',
                    'author_role' => 'YÃ¼ksek Divan KÄ±demli YazmanÄ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/1.png',
                    'is_breaking' => true,
                    'description' => "GÃ¶lge Dehlizleri'nin 3. katÄ±nda Kadim Lich Malgok'un beklenmedik nekromansi pususuna yakalanan 1. KeÅŸif Kolu'nda BaÅŸ MuhafÄ±z Braum 'DemiryÃ¼rek' ve beraberindeki 4 genÃ§ savaÅŸÃ§Ä± ÅŸehadet ÅŸerbetini iÃ§ti. Lonca merkezinde 3 gÃ¼nlÃ¼k resmi yas ilan edildi.",
                    'full_story' => "Valiant Shade YÃ¼ksek KomutanlÄ±ÄŸÄ± tarafÄ±ndan sabaha karÅŸÄ± yayÄ±nlanan acÄ± bildiriye gÃ¶re; GÃ¶lge Dehlizleri'nin mÃ¼hÃ¼rlÃ¼ 3. seviyesine rutin haritalama amacÄ±yla inen 'GÃ¼mÃ¼ÅŸ PenÃ§e' devriye birliÄŸi, kadim haritalarda iÅŸaretlenmemiÅŸ bir nekromansi pususuna dÃ¼ÅŸtÃ¼.

BirliÄŸe liderlik eden 28 yÄ±llÄ±k kÄ±demli BaÅŸ MuhafÄ±z Braum 'DemiryÃ¼rek', arkasÄ±ndaki acemi bÃ¼yÃ¼cÃ¼leri ve yaralÄ± izcileri kurtarabilmek iÃ§in yÄ±kÄ±lan mermer kemerin altÄ±nda tek baÅŸÄ±na kalkan oluÅŸturdu. YanÄ±ndaki cesur silah arkadaÅŸlarÄ± Kolcu Kaelen, ÅÃ¶valye Varis, Ã‡Ä±rak Arcanist Alistair ve SÄ±hhiyeci Lyanna, son nefeslerine kadar mevzilerini terk etmedi.

Hayatta kalan izcilerin ifadesine gÃ¶re, Braum kalkanÄ±nÄ± dehlizin giriÅŸine mÄ±hlayarak arkasÄ±ndaki 8 lonca Ã¼yesinin gÃ¼venle tahliye olmasÄ±nÄ± saÄŸladÄ±. Åehitlerimizin naaÅŸlarÄ±, Lonca ÅÃ¶valyeleri tarafÄ±ndan sabahÄ±n ilk Ä±ÅŸÄ±klarÄ±yla Meclis Åapeli'ne getirildi.

Lonca UstasÄ± Lord Gerald fermanÄ±yla: Valiant Shade bayraklarÄ± yarÄ±ya indirilmiÅŸ, bÃ¼yÃ¼k salonda anma sunaÄŸÄ± kurulmuÅŸ ve ÅŸehitlerimizin ailelerine Lonca Hazinesi'nden Ã¶mÃ¼r boyu maaÅŸ tahsis edilmiÅŸtir. RuhlarÄ± Kadim IÅŸÄ±k'ta huzur bulsun.",
                    'testimony' => "Braum son nefesinde kalkanÄ±nÄ± yere vurdu ve 'ArkamÄ± kollamayÄ± bÄ±rakÄ±n, Ã§Ä±raklarÄ± dehlizden Ã§Ä±karÄ±n!' diye kÃ¼kredi. Onun sayesinde bugÃ¼n nefes alabiliyoruz. â€” Kurtulan Ä°vece Kolcusu Finn",
                    'stats' => [
                        ['label' => 'Åehit SayÄ±sÄ±', 'value' => '5 Kahraman', 'color' => 'text-danger'],
                        ['label' => 'KurtarÄ±lan Can', 'value' => '8 Ä°vece', 'color' => 'text-success'],
                        ['label' => 'Lonca Durumu', 'value' => '3 GÃ¼n Yas', 'color' => 'text-warning'],
                        ['label' => 'Tehlike Kodu', 'value' => 'KÄ±rmÄ±zÄ± (Lich)', 'color' => 'text-danger'],
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
                    'title' => "KÄ±zÄ±l Han FÄ±sÄ±ltÄ±larÄ±: Valiant Shade YÃ¶neticisinin GÃ¶lge Cemiyeti ile Gizli Gece BuluÅŸmasÄ±!",
                    'category' => 'Dedikodu & Skandal',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flame',
                    'tag' => 'Meyhane Dedikodusu',
                    'tag_icon' => 'icon-base ti tabler-message-2-question',
                    'date_text' => 'DÃ¼n Gece YarÄ±sÄ±',
                    'published_date' => '30 EylÃ¼l 2026, 23:45',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_tavern_whispers.jpg',
                    'location_badge' => 'KÄ±zÄ±l Han - Arka Loca No: 4',
                    'author' => 'Meyhaneci Barnaby',
                    'author_role' => 'KÄ±zÄ±l Han BaÅŸ Ä°ÅŸletmecisi & Åehir GÃ¶zcÃ¼sÃ¼',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/2.png',
                    'is_breaking' => false,
                    'description' => "KÄ±zÄ±l Han'Ä±n loÅŸ arka masalarÄ±nda dÃ¼n gece fÄ±sÄ±ltÄ±lar koptu: Valiant Shade YÃ¼ksek Meclisi'nden kukuletalÄ± bir yÃ¶neticinin, yasaklÄ± GÃ¶lge HÄ±rsÄ±zlar Cemiyeti sÃ¶zcÃ¼sÃ¼yle yÃ¼klÃ¼ miktarda kara altÄ±n ve kan mÃ¼hÃ¼rlÃ¼ bir antlaÅŸma karÅŸÄ±lÄ±ÄŸÄ±nda gizlice el sÄ±kÄ±ÅŸtÄ±ÄŸÄ± iddia ediliyor!",
                    'full_story' => "KÄ±zÄ±l Han'Ä±n fÄ±Ã§Ä±larÄ±n arkasÄ±ndaki loÅŸ kÃ¶ÅŸelerinde dÃ¼n gece Ã¶yle fÄ±sÄ±ltÄ±lar dolaÅŸtÄ± ki, cÃ¼celer bile biralarÄ±nÄ± yudumlamayÄ± bÄ±raktÄ±!

Saat gece yarÄ±sÄ±nÄ± Ã¼Ã§ Ã§eyrek geÃ§e, saÄŸ omzunda Valiant Shade'in altÄ±n sÄ±rmalÄ± kurdelesini taÅŸÄ±dÄ±ÄŸÄ± sÃ¶ylenen uzun boylu, gri pelerinli bir ÅŸahÄ±s hanÄ±n servis kapÄ±sÄ±ndan iÃ§eri sÃ¼zÃ¼ldÃ¼. Arka localardan 4 numaralÄ± masada, yÃ¼zÃ¼nde gÃ¼mÃ¼ÅŸ bir tilki maskesi bulunan ve ÅŸehrin tekinsiz yeraltÄ± dÃ¼nyasÄ±ndan olduÄŸu bilinen 'GÃ¶lge ElÃ§i' onu bekliyordu.

Masaya servis yapan garson cÃ¼ce Thorbjorn'un yeminle anlattÄ±ÄŸÄ±na gÃ¶re; masaya bÄ±rakÄ±lan kadife keseden dÃ¶kÃ¼len paralar sÄ±radan imparatorluk altÄ±nÄ± deÄŸil, mÃ¼hÃ¼rlÃ¼ 'Kara ZÃ¼mrÃ¼t' sikkeleriydi! Ä°kili, Ã¼zerinde mavi bÃ¼yÃ¼ alevi tÃ¼ten bir antlaÅŸma parÅŸÃ¶menini kanla imzaladÄ±ktan sonra ÅŸehrin su kanallarÄ±na doÄŸru hÄ±zla gÃ¶zden kayboldu.

Lonca DivanÄ± iddialarÄ± henÃ¼z resmi olarak doÄŸrulamasa da, Lonca HazinedarÄ±'nÄ±n son 2 haftadÄ±r kayÄ±p olan 'Obsidyen Mahzen AnahtarÄ±' ile bu gizli buluÅŸmanÄ±n bir baÄŸÄ± olup olmadÄ±ÄŸÄ± sokaklarda en Ã§ok konuÅŸulan komplo teorisi haline geldi.",
                    'testimony' => "Kendi gÃ¶zlerimle gÃ¶rdÃ¼m efendim! O pelerinin altÄ±ndaki broÅŸ dÃ¼pedÃ¼z YÃ¼ksek Meclis'in kartal armasÄ±ydÄ±. Kadehleri tokuÅŸturmadÄ±lar bile, parayÄ± alÄ±p karanlÄ±ÄŸa karÄ±ÅŸtÄ±lar. â€” CÃ¼ce Thorbjorn",
                    'stats' => [
                        ['label' => 'GÃ¼venilirlik', 'value' => '%75 Ä°ddia', 'color' => 'text-warning'],
                        ['label' => 'GÃ¶rgÃ¼ TanÄ±ÄŸÄ±', 'value' => '3 Masada', 'color' => 'text-info'],
                        ['label' => 'ÅÃ¼pheli Belge', 'value' => 'Kan MÃ¼hÃ¼rlÃ¼', 'color' => 'text-danger'],
                        ['label' => 'RÃ¼ÅŸvet Tahmini', 'value' => '5.000 Kara Sikke', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'FÄ±sÄ±ltÄ±yÄ± Ä°ncele',
                    'button_url' => '/valiant-shade/pages/news#news-2',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-eye',
                    'order' => 2,
                    'is_active' => true,
                    'views_count' => 2180,
                ],
                [
                    'id' => 3,
                    'title' => "Lonca HazinedarÄ±'na Gece YarÄ±sÄ± Suikast GiriÅŸimi: Zehirli Ok ve BÄ±rakÄ±lan Kara Mektup",
                    'category' => 'Suikast & Ä°hanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-dagger',
                    'tag' => 'Acil GÃ¼venlik UyarÄ±sÄ±',
                    'tag_icon' => 'icon-base ti tabler-alert-triangle',
                    'date_text' => '12 saat Ã¶nce',
                    'published_date' => '01 Ekim 2026, 10:30',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_conspiracy.jpg',
                    'location_badge' => 'Hazine Kulesi - MuhafÄ±z Avlusu',
                    'author' => 'MuhafÄ±z YÃ¼zbaÅŸÄ±sÄ± Torin',
                    'author_role' => 'Kale GÃ¼venliÄŸi ve AsayiÅŸ Åefi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/3.png',
                    'is_breaking' => false,
                    'description' => "Lonca HazinedarÄ± Lord Vance, Ã§alÄ±ÅŸma odasÄ±nda bÃ¼tÃ§e kayÄ±tlarÄ±nÄ± incelerken pencereyi delen zehirli bir tatar yayÄ± okuyla hedef alÄ±ndÄ±! Vance kÄ±l payÄ± kurtulurken, saldÄ±rganÄ±n geride bÄ±raktÄ±ÄŸÄ± parÅŸÃ¶mende 'Kasa aÃ§Ä±lacak, bedel Ã¶denecek' yazdÄ±ÄŸÄ± bildirildi.",
                    'full_story' => "Valiant Shade Hazine Dairesi dÃ¼n gece kanlÄ± bir suikast giriÅŸimine sahne oldu. Saat 01:20 sularÄ±nda, lonca surlarÄ±nÄ±n doÄŸu kulesinden ateÅŸlenen gÃ¶lge iÅŸlemeli zehirli bir arbalet oku, Lonca HazinedarÄ± Lord Vance'in tam kulaÄŸÄ±nÄ±n yanÄ±ndan geÃ§erek arkasÄ±ndaki meÅŸe kitaplÄ±ÄŸa saplandÄ±.

Okun ucunda sÃ¼rÃ¼len zehrin nadir bulunan 'Wyvern Nefesi' toksini olduÄŸu simyacÄ±lar tarafÄ±ndan doÄŸrulandÄ±. Tek bir sÄ±yrÄ±ÄŸÄ±n bile dakikalar iÃ§inde kalbi durdurmaya yeteceÄŸi bu Ã¶lÃ¼mcÃ¼l sÄ±vÄ±nÄ±n karaborsadan temin edildiÄŸi dÃ¼ÅŸÃ¼nÃ¼lÃ¼yor.

SuikastÃ§Ä±nÄ±n kaÃ§arken dÃ¼ÅŸÃ¼rdÃ¼ÄŸÃ¼ siyah ipek mendilde, Valiant Shade'in rakip hanedanlarÄ±na iÅŸaret eden gizli bir kabartma mÃ¼hÃ¼r ve kÄ±sa bir not bulundu: 'Kasa aÃ§Ä±lacak, bedel kanla Ã¶denecek.'

MuhafÄ±z YÃ¼zbaÅŸÄ±sÄ± Torin kalede kÄ±rmÄ±zÄ± alarm ilan etti; Hazine Kulesi'ne giriÅŸ Ã§Ä±kÄ±ÅŸlar kÄ±sÄ±tlandÄ± ve tÃ¼m lonca Ã¼yelerine ÅŸÃ¼pheli kiÅŸileri derhal bildirmeleri Ã§aÄŸrÄ±sÄ±nda bulunuldu.",
                    'testimony' => "Ok meÅŸe tahtaya girdiÄŸinde havaya yeÅŸil bir duman yÃ¼kseldi. Hedef doÄŸrudan boynumdu. LoncamÄ±zÄ±n iÃ§indeki kÃ¶stebek bulunana dek kimseye huzur yok. â€” Lord Vance",
                    'stats' => [
                        ['label' => 'Hedef', 'value' => 'Lord Vance', 'color' => 'text-white'],
                        ['label' => 'KullanÄ±lan Zehir', 'value' => 'Wyvern Nefesi', 'color' => 'text-danger'],
                        ['label' => 'Alarm Seviyesi', 'value' => 'Kod KÄ±rmÄ±zÄ±', 'color' => 'text-danger'],
                        ['label' => 'Kelle Ã–dÃ¼lÃ¼', 'value' => '2.500 AltÄ±n', 'color' => 'text-warning'],
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
                    'title' => "Ejderha GeÃ§idi Åehidi BaÅŸ BÃ¼yÃ¼cÃ¼ Lyra iÃ§in BÃ¼yÃ¼k Åapel'de GÃ¶rkemli Cenaze TÃ¶reni",
                    'category' => 'Ã–lÃ¼m & Anma',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-flame-off',
                    'tag' => 'Åapel TÃ¶reni & Veda',
                    'tag_icon' => 'icon-base ti tabler-ribbon-health',
                    'date_text' => '2 GÃ¼n Ã–nce',
                    'published_date' => '29 EylÃ¼l 2026, 14:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_heroes_funeral.jpg',
                    'location_badge' => 'GÃ¶k TapÄ±naÄŸÄ± & Åehitler MeydanÄ±',
                    'author' => 'YÃ¼ksek Patrik Aurelius',
                    'author_role' => 'GÃ¼neÅŸ Åapeli BaÅŸ Rahibi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/4.png',
                    'is_breaking' => false,
                    'description' => "Ejderha GeÃ§idi Seferi'nde birliÄŸini korumak iÃ§in kendini alev hortumunun Ã¶nÃ¼ne siper eden BaÅŸ BÃ¼yÃ¼cÃ¼ Lyra Stormchaser, yÃ¼zlerce lonca Ã¼yesi ve halkÄ±n katÄ±ldÄ±ÄŸÄ± gÃ¶rkemli bir tÃ¶renle sonsuzluÄŸa uÄŸurlandÄ±.",
                    'full_story' => "Åehrin sokaklarÄ± dÃ¼n sabah erken saatlerden itibaren siyah bayraklar ve beyaz zambaklarla donatÄ±ldÄ±. Ejderha GeÃ§idi'nde alevler saÃ§an KÄ±rmÄ±zÄ± Ejderha'nÄ±n ateÅŸ dalgasÄ±nÄ± 'YÄ±ldÄ±z KalkanÄ±' bÃ¼yÃ¼sÃ¼yle durduran ve 14 gencecik lonca Ã¼yesinin hayatÄ±nÄ± kurtarÄ±rken bedenini feda eden BaÅŸ BÃ¼yÃ¼cÃ¼ Lyra Stormchaser iÃ§in veda gÃ¼nÃ¼ydÃ¼.

GÃ¶k TapÄ±naÄŸÄ±'ndan Åehitler MeydanÄ±'na kadar uzanan kortejde, loncanÄ±n en kÄ±demli 12 paladini Lyra'nÄ±n asasÄ±nÄ± ve bÃ¼yÃ¼cÃ¼ cÃ¼ppesini taÅŸÄ±dÄ±. Lonca UstasÄ±, Lyra'ya Ã¶lÃ¼mÃ¼nden sonra 'Valiant Shade ÃœstÃ¼n Cesaret ve FedakarlÄ±k MadalyasÄ±' takdim etti.

Lyra'nÄ±n vasiyeti Ã¼zerine lonca kÃ¼tÃ¼phanesine baÄŸÄ±ÅŸladÄ±ÄŸÄ± kadim bÃ¼yÃ¼ parÅŸÃ¶menleri korumaya alÄ±nÄ±rken, adÄ± Lonca Meclis Salonu'nun giriÅŸindeki AltÄ±n Mermer Kitabe'ye kazÄ±ndÄ±.",
                    'testimony' => "Alevler Ã¼stÃ¼mÃ¼ze gelirken tek bir adÄ±m bile geri Ã§ekilmedi. 'KaÃ§Ä±n!' dedi, 'Ben Ä±ÅŸÄ±ÄŸÄ± tutarÄ±m'. O Ä±ÅŸÄ±k hiÃ§ sÃ¶nmeyecek. â€” Ã‡Ä±rak BÃ¼yÃ¼cÃ¼ Selene",
                    'stats' => [
                        ['label' => 'KurtardÄ±ÄŸÄ± Can', 'value' => '14 Ãœye', 'color' => 'text-success'],
                        ['label' => 'TÃ¶ren KatÄ±lÄ±mÄ±', 'value' => '800+ KiÅŸi', 'color' => 'text-info'],
                        ['label' => 'Liyakat NiÅŸanÄ±', 'value' => 'AltÄ±n Madalya', 'color' => 'text-warning'],
                        ['label' => 'AnÄ±t Kabri', 'value' => 'BÃ¼yÃ¼cÃ¼ler BahÃ§esi', 'color' => 'text-primary'],
                    ],
                    'button_text' => 'Anma Kitabesini GÃ¶r',
                    'button_url' => '/valiant-shade/pages/news#news-4',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-flower',
                    'order' => 4,
                    'is_active' => true,
                    'views_count' => 1740,
                ],
                [
                    'id' => 5,
                    'title' => 'SimyacÄ±lar Ã‡arÅŸÄ±sÄ± KaynÄ±yor: Sahte DiriliÅŸ ve Mana Ä°ksirleri Piyasada mÄ±?',
                    'category' => 'Dedikodu & UyarÄ±',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flask',
                    'tag' => 'Ã‡arÅŸÄ± TeftiÅŸi & Skandal',
                    'tag_icon' => 'icon-base ti tabler-search',
                    'date_text' => 'DÃ¼n 18:40',
                    'published_date' => '30 EylÃ¼l 2026, 18:40',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_arcane_academy.jpg',
                    'location_badge' => 'AÅŸaÄŸÄ± Åehir SimyacÄ±lar Ã‡Ä±kmazÄ±',
                    'author' => 'Usta SimyacÄ± Althea',
                    'author_role' => 'Lonca TeftiÅŸ Heyeti Temsilcisi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/5.png',
                    'is_breaking' => false,
                    'description' => "Son seferden dÃ¶nen maceracÄ±larÄ±n iksir ÅŸiÅŸelerinde beklenen ÅŸifa yerine sadece ÅŸekerli su ve parlayan yosun tozu bulduÄŸu iddiasÄ± Ã¼zerine lonca teftiÅŸ mÃ¼fettiÅŸleri Ã§arÅŸÄ±daki 3 dÃ¼kkanÄ± ani baskÄ±nla mÃ¼hÃ¼rledi.",
                    'full_story' => "Son gÃ¼nlerde zindan gÃ¶revlerinden dÃ¶nen savaÅŸÃ§Ä±larÄ±n ardÄ± ardÄ±na saÄŸlÄ±k ocaÄŸÄ±na baÅŸvurmasÄ± Ã¼zerine korkunÃ§ bir skandal patlak verdi. Zindan canavarlarÄ±yla Ã§arpÄ±ÅŸÄ±rken tÃ¼ketilen 'BÃ¼yÃ¼k Can Ä°ksirleri'nin yaralarÄ± iyileÅŸtirmek bir yana, savaÅŸÃ§Ä±larÄ±n gÃ¶rÃ¼ÅŸÃ¼nÃ¼ bulanÄ±klaÅŸtÄ±rdÄ±ÄŸÄ± ve mide spazmlarÄ±na yol aÃ§tÄ±ÄŸÄ± ortaya Ã§Ä±ktÄ±!

Lonca TeftiÅŸ Heyeti, AÅŸaÄŸÄ± Åehir'deki Ã¼Ã§ simya atÃ¶lyesine gece baskÄ±nÄ± dÃ¼zenledi. Ele geÃ§irilen yÃ¼zlerce ÅŸiÅŸenin mÃ¼hÃ¼rlerinin sahte olduÄŸu, iÃ§eriÄŸinde mandrake kÃ¶kÃ¼ yerine ucuz bataklÄ±k mantarlarÄ± ve mavi gÄ±da boyasÄ± kullanÄ±ldÄ±ÄŸÄ± tespit edildi.

Ä°ddialara gÃ¶re sahtekarlÄ±k ÅŸebekesi, lonca armalÄ± kutularÄ± kopyalayarak ucuz fiyatla acemi maceracÄ±lara pazarlÄ±yordu. Sahte iksirleri satan iki tÃ¼ccar Lonca ZindanÄ±'na kapatÄ±ldÄ±; lonca hekimleri tÃ¼m Ã¼yelere yalnÄ±zca Lonca Resmi Eczanesi'nden iksir tedarik etmeleri yÃ¶nÃ¼nde sert bir uyarÄ± yayÄ±nladÄ±.",
                    'testimony' => "Can havliyle iksiri kafaya diktiÄŸimde ÅŸifa yerine aÄŸzÄ±mdan mavi kÃ¶pÃ¼kler Ã§Ä±ktÄ±! Neredeyse goblinlerin elinde can verecektim. â€” SavaÅŸÃ§Ä± Dan",
                    'stats' => [
                        ['label' => 'MÃ¼hÃ¼rlenen DÃ¼kkan', 'value' => '3 AtÃ¶lye', 'color' => 'text-danger'],
                        ['label' => 'El Konulan ÅiÅŸe', 'value' => '420 Adet', 'color' => 'text-warning'],
                        ['label' => 'Etkilenen Ãœye', 'value' => '12 MaceracÄ±', 'color' => 'text-info'],
                        ['label' => 'Hapis CezasÄ±', 'value' => 'MÃ¼ebbet Zindan', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Sahte Ä°ksir Listesi',
                    'button_url' => '/valiant-shade/pages/news#news-5',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-alert-circle',
                    'order' => 5,
                    'is_active' => true,
                    'views_count' => 1310,
                ],
                [
                    'id' => 6,
                    'title' => 'Kadim Ejderha Ignis Dize Getirildi: Lonca Hazinesine 14.500 AltÄ±n ve Kadim Eserler Eklendi',
                    'category' => 'Sefer Zaferi',
                    'badge_color' => 'success',
                    'category_icon' => 'icon-base ti tabler-trophy',
                    'tag' => 'Efsanevi Zafer',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '3 GÃ¼n Ã–nce',
                    'published_date' => '28 EylÃ¼l 2026, 11:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_dragon_quest.jpg',
                    'location_badge' => 'Ejderha Zirvesi Harabeleri',
                    'author' => 'General Ragnor',
                    'author_role' => '1. SaldÄ±rÄ± Lejyonu BaÅŸkomutanÄ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/6.png',
                    'is_breaking' => false,
                    'description' => "3 hafta sÃ¼ren amansÄ±z kuÅŸatmanÄ±n ardÄ±ndan Kadim Alev EjderhasÄ± Ignis maÄŸlup edildi. EjderhanÄ±n ininden Ã§Ä±karÄ±lan Antik Hazine loncamÄ±za taÅŸÄ±ndÄ±; sefere katÄ±lan tÃ¼m birliklere yÃ¼klÃ¼ prim daÄŸÄ±tÄ±lÄ±yor.",
                    'full_story' => "Valiant Shade'in en tecrÃ¼beli 30 elit savaÅŸÃ§Ä±sÄ±, haftalardÄ±r Ejderha Zirvesi'nin dondurucu zirvelerinde kamp kurarak Kadim Ignis'in inini abluka altÄ±nda tutuyordu. DÃ¼n sabah ÅŸafak sÃ¶kerken baÅŸlayan nihai taarruzda, ejderhanÄ±n alev menzili bÃ¼yÃ¼cÃ¼lerimizin dondurucu fÄ±rtÄ±nasÄ±yla kÄ±rÄ±ldÄ±.

General Ragnor'un bizzat vurduÄŸu son darbeyle dev yaratÄ±k yere serildi. MaÄŸara derinliklerinden Ã§Ä±karÄ±lan ganimetler arasÄ±nda; Antik KrallÄ±k'tan kalma 14.500 altÄ±n sikke, 6 adet efsanevi rÃ¼nik silah ve lonca kÃ¼tÃ¼phanesini aydÄ±nlatacak 'GÃ¼neÅŸ TaÅŸÄ±' yer alÄ±yor.

Zafer kutlamalarÄ± bu akÅŸam Lonca Meclis Salonu'nda fÄ±Ã§Ä±larÄ±n patlatÄ±lmasÄ±yla baÅŸlayacak!",
                    'testimony' => "KalkanÄ±mÄ±n Ã¼zerine ateÅŸ pÃ¼skÃ¼rttÃ¼ÄŸÃ¼nde kemiklerimin eridiÄŸini hissettim ama bir santim bile geri adÄ±m atmadÄ±k. Bu zafer tÃ¼m Valiant Shade ailesinindir! â€” General Ragnor",
                    'stats' => [
                        ['label' => 'KazanÄ±lan AltÄ±n', 'value' => '+14.500 GP', 'color' => 'text-warning'],
                        ['label' => 'KazanÄ±lan XP', 'value' => '+8.500 XP', 'color' => 'text-success'],
                        ['label' => 'RÃ¼nik Eser', 'value' => '6 Kadim Silah', 'color' => 'text-info'],
                        ['label' => 'KayÄ±p Durumu', 'value' => '0 Can KaybÄ±', 'color' => 'text-success'],
                    ],
                    'button_text' => 'Ganimet PaylaÅŸÄ±mÄ±nÄ± GÃ¶r',
                    'button_url' => '/valiant-shade/pages/news#news-6',
                    'button_color' => 'success',
                    'button_icon' => 'icon-base ti tabler-coin',
                    'order' => 6,
                    'is_active' => true,
                    'views_count' => 3120,
                ],
                [
                    'id' => 7,
                    'title' => "YÃ¼ksek Divan KararÄ±: 'KÄ±zÄ±l Maskeli Ä°syancÄ±lar' Ä°Ã§in CanlÄ± ya da Ã–lÃ¼ 5.000 AltÄ±n Kelle Ã–dÃ¼lÃ¼",
                    'category' => 'Lonca Meclisi',
                    'badge_color' => 'primary',
                    'category_icon' => 'icon-base ti tabler-gavel',
                    'tag' => 'Resmi Ferman',
                    'tag_icon' => 'icon-base ti tabler-file-certificate',
                    'date_text' => '4 GÃ¼n Ã–nce',
                    'published_date' => '27 EylÃ¼l 2026, 16:30',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_hall_council.jpg',
                    'location_badge' => 'Valiant Shade YÃ¼ksek Divan Salonu',
                    'author' => 'ÅansÃ¶lye Valen',
                    'author_role' => 'YÃ¼ksek Adalet ve Divan BaÅŸkanÄ±',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/7.png',
                    'is_breaking' => false,
                    'description' => "LoncanÄ±n kervan yollarÄ±nÄ± basÄ±p erzak konvoylarÄ±nÄ± yaÄŸmalayan KÄ±zÄ±l Maske Ã§etesine karÅŸÄ± YÃ¼ksek Divan'dan tarihi ferman Ã§Ä±ktÄ±: ElebaÅŸÄ± 'Tek GÃ¶zlÃ¼ Silas' ve kurmaylarÄ±nÄ±n baÅŸÄ±na rekor kelle Ã¶dÃ¼lÃ¼ kondu.",
                    'full_story' => "Valiant Shade YÃ¼ksek DivanÄ±, son 1 ay iÃ§inde Kuzey Ticaret Yolu Ã¼zerinde vuku bulan 5 kervan baskÄ±nÄ±nÄ± deÄŸerlendirmek Ã¼zere olaÄŸanÃ¼stÃ¼ toplandÄ±.

Kervan muhafÄ±zlarÄ±ndan 3 yoldaÅŸÄ±mÄ±zÄ±n ÅŸehit olmasÄ± ve lonca demirhanesine giden 2 araba dolusu mithril cevherinin Ã§alÄ±nmasÄ± bardaÄŸÄ± taÅŸÄ±ran son damla oldu. ÅansÃ¶lye Valen imzalÄ± fermana gÃ¶re:

1. KÄ±zÄ±l Maskeli Ã§etenin elebaÅŸÄ± 'Tek GÃ¶zlÃ¼ Silas'Ä±n kellesine 5.000 AltÄ±n,
2. TeÄŸmenlerinin her biri iÃ§in 1.500 AltÄ±n,
3. Ã‡alÄ±nan mithril cevherini getiren birliÄŸe cevherin deÄŸerinin %30'u peÅŸin ikramiye Ã¶denecektir.

TÃ¼m lonca paralÄ± askerleri ve serbest kÄ±lÄ±Ã§lar bu fermandan yararlanabilir.",
                    'testimony' => "Ticaret yollarÄ±mÄ±zÄ± tehdit eden hiÃ§bir eÅŸkÄ±ya cezasÄ±z kalmayacak. Adalet ya kÄ±lÄ±Ã§la ya da iple tecelli edecektir. â€” ÅansÃ¶lye Valen",
                    'stats' => [
                        ['label' => 'BaÅŸ Ã–dÃ¼lÃ¼', 'value' => '5.000 AltÄ±n', 'color' => 'text-warning'],
                        ['label' => 'Ã‡ete GÃ¼cÃ¼', 'value' => '~40 Haydut', 'color' => 'text-danger'],
                        ['label' => 'Operasyon BÃ¶lgesi', 'value' => 'Kuzey GeÃ§itleri', 'color' => 'text-info'],
                        ['label' => 'Yetki Durumu', 'value' => 'CanlÄ± veya Ã–lÃ¼', 'color' => 'text-white'],
                    ],
                    'button_text' => 'FermanÄ± Ä°mzala & KatÄ±l',
                    'button_url' => '/valiant-shade/pages/news#news-7',
                    'button_color' => 'primary',
                    'button_icon' => 'icon-base ti tabler-file-text',
                    'order' => 7,
                    'is_active' => true,
                    'views_count' => 1650,
                ],
                [
                    'id' => 8,
                    'title' => "Obsidyen Hisar'da UyanÄ±ÅŸ: GÃ¶kyÃ¼zÃ¼nde KÄ±rmÄ±zÄ± Ay ve Ã‡Ä±ÄŸlÄ±k Sesleri",
                    'category' => 'Gizem & Kehanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-moon',
                    'tag' => 'KaranlÄ±k Kehanet',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '5 GÃ¼n Ã–nce',
                    'published_date' => '26 EylÃ¼l 2026, 03:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_realm.jpg',
                    'location_badge' => 'Yasak GÃ¶lge Vadisi SÄ±nÄ±rÄ±',
                    'author' => 'Kahin Morvath',
                    'author_role' => 'YÄ±ldÄ±z GÃ¶zlemevi BaÅŸ AstroloÄŸu',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/8.png',
                    'is_breaking' => false,
                    'description' => "YÃ¼zyÄ±llardÄ±r mÃ¼hÃ¼rlÃ¼ duran Obsidyen Hisar'Ä±n kulelerinden gÃ¶kyÃ¼zÃ¼ne kan kÄ±rmÄ±zÄ±sÄ± bir Ä±ÅŸÄ±k sÃ¼tunu yÃ¼kseldi. Ã‡evredeki kÃ¶ylÃ¼ler daÄŸlardan gelen zincir ÅŸakÄ±rtÄ±larÄ± ve Ã§Ä±ÄŸlÄ±k sesleri nedeniyle kÃ¶ylerini boÅŸaltÄ±yor.",
                    'full_story' => "GÃ¶lge Vadisi sÄ±nÄ±rÄ±ndaki gÃ¶zlem kulelerimiz dÃ¼n gece dehÅŸet verici doÄŸaÃ¼stÃ¼ olaylar kaydetti. Gece yarÄ±sÄ± saat tam 03:00'te ay kÄ±rmÄ±zÄ±ya bÃ¼rÃ¼ndÃ¼ ve Obsidyen Hisar'Ä±n tepe noktasÄ±ndan bulutlarÄ± delen karanlÄ±k bir enerji dalgasÄ± yayÄ±ldÄ±.

GÃ¶zlemciler, hisarÄ±n Ã§atlaklarÄ±ndan akan mor alevleri ve taÅŸ duvarlara Ã§arpan ruhani haykÄ±rÄ±ÅŸlarÄ± raporladÄ±. Astrologlar heyeti, bu olayÄ±n 300 yÄ±l Ã¶nce hapsedilen 'GÃ¶lge HÃ¼kÃ¼mdarÄ± Malakor'un mÃ¼hrÃ¼nÃ¼n zayÄ±fladÄ±ÄŸÄ±na dair aÃ§Ä±k bir iÅŸaret olduÄŸunu vurguluyor.

Lonca UstasÄ±, bÃ¶lgeye deneyimli izci birlikleri gÃ¶nderilmesi ve sÄ±nÄ±r karakollarÄ±nÄ±n tahkim edilmesi talimatÄ±nÄ± verdi.",
                    'testimony' => "RÃ¼zgar artÄ±k daÄŸ kokmuyor, kÃ¼kÃ¼rt ve bin yÄ±llÄ±k mezar topraÄŸÄ± kokuyor. MÃ¼hÃ¼r kÄ±rÄ±lÄ±rsa dÃ¼nya karanlÄ±ÄŸa boÄŸulur. â€” Kahin Morvath",
                    'stats' => [
                        ['label' => 'Tehdit Seviyesi', 'value' => 'Omega SÄ±nÄ±fÄ±', 'color' => 'text-danger'],
                        ['label' => 'GÃ¶zlem NoktasÄ±', 'value' => 'Kuzey Kulesi', 'color' => 'text-warning'],
                        ['label' => 'Tahliye', 'value' => '2 KÃ¶y BoÅŸaldÄ±', 'color' => 'text-info'],
                        ['label' => 'MÃ¼hÃ¼r Durumu', 'value' => 'Kritik Ã‡atlak', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Kehanet ParÅŸÃ¶menini Oku',
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
                    'testimony' => "With his dying breath, Braum slammed his shield down and roared: 'Stop covering my back, get the apprentices out!' Because of him, we breathe today. â€” Ranger Finn, Survivor",
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
                    'description' => "Whispers broke out in the dim back corners of Red Tavern: a hooded executive from Valiant Shade High Council allegedly shook hands secretly with a forbidden Shadow Thieves Guild emissary for dark gold and a blood-sealed pact!",
                    'full_story' => "Whispers circulated through the dim corners behind the ale barrels at Red Tavern last night, causing even the stoutest dwarves to pause in their drinking!

At three quarters past midnight, a tall figure in a grey cloak sporting the golden sash of Valiant Shade slipped through the service door. Waiting in booth 4 was the 'Shadow Emissary' wearing a silver fox mask, known from the city's murky underworld.

According to dwarf server Thorbjorn, the purse set upon the table spilled not ordinary imperial gold, but minted 'Dark Emerald' bullion! The pair signed a parchment smoldering with blue sorcerous flame before vanishing into the city sewers.

While the High Council has not confirmed the rumors, whispers link this meeting with the missing 'Obsidian Vault Key' unaccounted for these past two weeks.",
                    'testimony' => "I saw the clasp beneath the cloak with my own eyesâ€”the eagle crest of the High Council. They didn't even clink mugs; took the coin and dissolved into the dark. â€” Dwarf Thorbjorn",
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
                    'testimony' => "Green smoke hissed from the wood where the bolt struck. The target was my throat. There will be no peace until the traitor within is exposed. â€” Lord Vance",
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
                    'description' => "Archmage Lyra Stormchaser, who shielded her unit against a flaming vortex during the Dragon Pass Expedition, was laid to eternal rest with a grand ceremony attended by hundreds of guild members and citizens.",
                    'full_story' => "Streets were draped with black pennants and white lilies from early dawn. It was a day of mourning for Archmage Lyra Stormchaser, who conjured a 'Starlight Aegis' against the Red Dragon's inferno, giving her life to save 14 young guild members.

In a solemn procession from the Sky Temple to Martyrs Square, 12 senior paladins carried Lyra's staff and vestments. The Guild Master posthumously awarded her the 'Valiant Shade Medal of Valor'.",
                    'testimony' => "As the flames rushed down, she didn't yield an inch. 'Fly!' she yelled, 'I will hold the light'. That light shall never fade. â€” Apprentice Mage Selene",
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
                    'description' => "Guild inspection officers raided and sealed 3 shops in the bazaar after returning adventurers discovered their potion vials contained only sugar water and glowing moss dust instead of healing elixir.",
                    'full_story' => "A scandal erupted when multiple frontline fighters reported to the infirmary after drinking 'Greater Healing Draughts' that caused blurred vision and nausea instead of closing wounds.

Inspectors raided three Lower City ateliers, confiscating hundreds of counter-marked bottles filled with swamp lichen extract and food dye. Two illicit merchants have been imprisoned.",
                    'testimony' => "In dire peril I quaffed the flask, and blue froth erupted from my mouth instead of mend! I nearly fell to goblins. â€” Dan the Fighter",
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
                    'description' => "After a 3-week relentless siege, Ancient Flame Dragon Ignis was defeated. The ancient treasure retrieved from the dragon lair was transferred to our guild; handsome bonuses are distributed to all units.",
                    'full_story' => "Thirty of Valiant Shade's elite combatants blockaded the icy spires of Dragon Peak for weeks. At dawn yesterday, the final assault breached the fire drake's domain.

General Ragnor dealt the final blow. Recovered treasures include 14,500 ancient minted gold coins, 6 runic armaments, and the legendary 'Sun Stone' to illuminate the grand library.",
                    'testimony' => "When fire washed across my shield my marrow burned, yet we gave not an inch. This victory belongs to all Valiant Shade! â€” General Ragnor",
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
                    'testimony' => "No outlaw menacing our lifelines shall evade retribution. Justice shall be done by blade or noose. â€” Chancellor Valen",
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
                    'description' => "A blood-red pillar of light rose into the sky from the towers of the Obsidian Citadel, sealed for centuries. Surrounding villagers are evacuating their villages due to rattling chains and shrieks echoing from the mountains.",
                    'full_story' => "Border sentries recorded supernatural activity at midnight: the moon turned blood red and an energy pulse pierced the sky from the Obsidian Citadel.

Observers saw purple flames and ghostly shrieks beating upon stone walls. Astrologers warn that the seal binding Malakor the Shadow Lord is fracturing.",
                    'testimony' => "The wind no longer smells of stone, but of brimstone and centuries of sepulchral earth. If the seal shatters, darkness consumes all. â€” Prophet Morvath",
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
            $item['image_url'] = 'static/valiant-shade/assets/img/news/' . $imageFile;
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
                $filteredNews = $filteredNews->filter(fn($item) => in_array((int)$item->id, $allowedIds, true));
            } else {
                $filteredNews = $filteredNews->filter(function ($item) use ($activeCategory) {
                    return stripos($item->category, $activeCategory) !== false;
                });
            }
        }

        if (!empty($searchQuery)) {
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
        $deathNewsCount = $allNews->filter(fn($n) => in_array((int)$n->id, [1, 6], true) || stripos($n->category, 'Ã–lÃ¼m') !== false || stripos($n->category, 'Death') !== false)->count();
        $rumorNewsCount = $allNews->filter(fn($n) => in_array((int)$n->id, [4], true) || stripos($n->category, 'Dedikodu') !== false || stripos($n->category, 'Rumor') !== false || stripos($n->category, 'Gossip') !== false)->count();
        $victoryNewsCount = $allNews->filter(fn($n) => in_array((int)$n->id, [3, 5], true) || stripos($n->category, 'Zafer') !== false || stripos($n->category, 'Victory') !== false || stripos($n->category, 'Expedition') !== false)->count();
        $decreeNewsCount = $allNews->filter(fn($n) => in_array((int)$n->id, [2, 7], true) || stripos($n->category, 'Meclis') !== false || stripos($n->category, 'Ferman') !== false || stripos($n->category, 'Decree') !== false || stripos($n->category, 'Council') !== false)->count();

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

        if (!$newsItem) {
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
            $filename = 'news_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/' . $filename;
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
            $filename = 'news_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('static/valiant-shade/assets/img/news');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imageUrl = 'static/valiant-shade/assets/img/news/' . $filename;
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
     * Drag-drop sÄ±ralama: Ã¼stteki kart en yÃ¼ksek order deÄŸerini alÄ±r (gazete iÃ§ sayfa sÄ±rasÄ±).
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
        $news->is_active = !$news->is_active;
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
        if (class_exists(\Database\Seeders\NewsSeeder::class)) {
            $seeder = new \Database\Seeders\NewsSeeder();
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

        $message = 'Bu gazete sayÄ±sÄ± kilitli; haber durumu deÄŸiÅŸtirilemez.';

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
        $labels = [
            'victory' => 'Zafer kutlandÄ±',
            'mourning' => 'Yas tutuldu',
            'whisper' => 'Han fÄ±sÄ±ltÄ±sÄ±',
            'bounty' => 'Ferman kaydÄ±',
        ];

        \App\ValiantShade\Support\NewsEngagement::recordEditorialReaction(
            $news,
            $validated['reaction'],
            $labels[$validated['reaction']],
        );

        app(\App\ValiantShade\Services\PublicNewsService::class)->invalidatePublicFeed();

        return response()->json([
            'success' => true,
            'message' => 'EditÃ¶r tepkisi kaydedildi.',
            'stats' => $news->fresh()->stats,
        ]);
    }
}


<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\News;
use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\GazetteIssueService;
use App\ValiantShade\Support\GazetteNewsImage;
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
                    'title' => 'Gölge Dehlizleri Seferinde Ağır Kayıp: Baş Muhafız Braum ve 4 Keşif Eri Şehit Düştü',
                    'category' => 'Ölüm & Şehitler',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-skull',
                    'tag' => 'Lonca Yası & Anma',
                    'tag_icon' => 'icon-base ti tabler-candle',
                    'date_text' => 'Bugün 04:15',
                    'published_date' => '01 Ekim 2026, 04:15',
                    'read_time' => '4 dk okuma',
                    'image_file' => 'guild_fallen_memorial.jpg',
                    'location_badge' => 'Gölge Dehlizleri - 3. Mühürlü Kat',
                    'author' => 'Lonca Başkâtibi Eldrin',
                    'author_role' => 'Yüksek Divan Kıdemli Yazmanı',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/1.png',
                    'is_breaking' => true,
                    'description' => "Gölge Dehlizleri'nin 3. katında Kadim Lich Malgok'un beklenmedik nekromansi pususuna yakalanan 1. Keşif Kolu'nda Baş Muhafız Braum 'Demiryürek' ve beraberindeki 4 genç savaşçı şehadet şerbetini içti. Lonca merkezinde 3 günlük resmi yas ilan edildi.",
                    'full_story' => "Valiant Shade Yüksek Komutanlığı tarafından sabaha karşı yayınlanan acı bildiriye göre; Gölge Dehlizleri'nin mühürlü 3. seviyesine rutin haritalama amacıyla inen 'Gümüş Pençe' devriye birliği, kadim haritalarda işaretlenmemiş bir nekromansi pususuna düştü.

Birliğe liderlik eden 28 yıllık kıdemli Baş Muhafız Braum 'Demiryürek', arkasındaki acemi büyücüleri ve yaralı izcileri kurtarabilmek için yıkılan mermer kemerin altında tek başına kalkan oluşturdu. Yanındaki cesur silah arkadaşları Kolcu Kaelen, Şövalye Varis, Çırak Arcanist Alistair ve Sıhhiyeci Lyanna, son nefeslerine kadar mevzilerini terk etmedi.

Hayatta kalan izcilerin ifadesine göre, Braum kalkanını dehlizin girişine mıhlayarak arkasındaki 8 lonca üyesinin güvenle tahliye olmasını sağladı. Şehitlerimizin naaşları, Lonca Şövalyeleri tarafından sabahın ilk ışıklarıyla Meclis Şapeli'ne getirildi.

Lonca Ustası Lord Gerald fermanıyla: Valiant Shade bayrakları yarıya indirilmiş, büyük salonda anma sunağı kurulmuş ve şehitlerimizin ailelerine Lonca Hazinesi'nden ömür boyu maaş tahsis edilmiştir. Ruhları Kadim Işık'ta huzur bulsun.",
                    'testimony' => "Braum son nefesinde kalkanını yere vurdu ve 'Arkamı kollamayı bırakın, çırakları dehlizden çıkarın!' diye kükredi. Onun sayesinde bugün nefes alabiliyoruz. — Kurtulan İvece Kolcusu Finn",
                    'stats' => [
                        ['label' => 'Şehit Sayısı', 'value' => '5 Kahraman', 'color' => 'text-danger'],
                        ['label' => 'Kurtarılan Can', 'value' => '8 İvece', 'color' => 'text-success'],
                        ['label' => 'Lonca Durumu', 'value' => '3 Gün Yas', 'color' => 'text-warning'],
                        ['label' => 'Tehlike Kodu', 'value' => 'Kırmızı (Lich)', 'color' => 'text-danger'],
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
                    'title' => "Kızıl Han Fısıltıları: Valiant Shade Yöneticisinin Gölge Cemiyeti ile Gizli Gece Buluşması!",
                    'category' => 'Dedikodu & Skandal',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flame',
                    'tag' => 'Meyhane Dedikodusu',
                    'tag_icon' => 'icon-base ti tabler-message-2-question',
                    'date_text' => 'Dün Gece Yarısı',
                    'published_date' => '30 Eylül 2026, 23:45',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_tavern_whispers.jpg',
                    'location_badge' => 'Kızıl Han - Arka Loca No: 4',
                    'author' => 'Meyhaneci Barnaby',
                    'author_role' => 'Kızıl Han Baş İşletmecisi & Şehir Gözcüsü',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/2.png',
                    'is_breaking' => false,
                    'description' => "Kızıl Han'ın loş arka masalarında dün gece fısıltılar koptu: Valiant Shade Yüksek Meclisi'nden kukuletalı bir yöneticinin, yasaklı Gölge Hırsızlar Cemiyeti sözcüsüyle yüklü miktarda kara altın ve kan mühürlü bir antlaşma karşılığında gizlice el sıkıştığı iddia ediliyor!",
                    'full_story' => "Kızıl Han'ın fıçıların arkasındaki loş köşelerinde dün gece öyle fısıltılar dolaştı ki, cüceler bile biralarını yudumlamayı bıraktı!

Saat gece yarısını üç çeyrek geçe, sağ omzunda Valiant Shade'in altın sırmalı kurdelesini taşıdığı söylenen uzun boylu, gri pelerinli bir şahıs hanın servis kapısından içeri süzüldü. Arka localardan 4 numaralı masada, yüzünde gümüş bir tilki maskesi bulunan ve şehrin tekinsiz yeraltı dünyasından olduğu bilinen 'Gölge Elçi' onu bekliyordu.

Masaya servis yapan garson cüce Thorbjorn'un yeminle anlattığına göre; masaya bırakılan kadife keseden dökülen paralar sıradan imparatorluk altını değil, mühürlü 'Kara Zümrüt' sikkeleriydi! İkili, üzerinde mavi büyü alevi tüten bir antlaşma parşömenini kanla imzaladıktan sonra şehrin su kanallarına doğru hızla gözden kayboldu.

Lonca Divanı iddiaları henüz resmi olarak doğrulamasa da, Lonca Hazinedarı'nın son 2 haftadır kayıp olan 'Obsidyen Mahzen Anahtarı' ile bu gizli buluşmanın bir bağı olup olmadığı sokaklarda en çok konuşulan komplo teorisi haline geldi.",
                    'testimony' => "Kendi gözlerimle gördüm efendim! O pelerinin altındaki broş düpedüz Yüksek Meclis'in kartal armasıydı. Kadehleri tokuşturmadılar bile, parayı alıp karanlığa karıştılar. — Cüce Thorbjorn",
                    'stats' => [
                        ['label' => 'Güvenilirlik', 'value' => '%75 İddia', 'color' => 'text-warning'],
                        ['label' => 'Görgü Tanığı', 'value' => '3 Masada', 'color' => 'text-info'],
                        ['label' => 'Şüpheli Belge', 'value' => 'Kan Mühürlü', 'color' => 'text-danger'],
                        ['label' => 'Rüşvet Tahmini', 'value' => '5.000 Kara Sikke', 'color' => 'text-warning'],
                    ],
                    'button_text' => 'Fısıltıyı İncele',
                    'button_url' => '/valiant-shade/pages/news#news-2',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-eye',
                    'order' => 2,
                    'is_active' => true,
                    'views_count' => 2180,
                ],
                [
                    'id' => 3,
                    'title' => "Lonca Hazinedarı'na Gece Yarısı Suikast Girişimi: Zehirli Ok ve Bırakılan Kara Mektup",
                    'category' => 'Suikast & İhanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-dagger',
                    'tag' => 'Acil Güvenlik Uyarısı',
                    'tag_icon' => 'icon-base ti tabler-alert-triangle',
                    'date_text' => '12 saat önce',
                    'published_date' => '01 Ekim 2026, 10:30',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_conspiracy.jpg',
                    'location_badge' => 'Hazine Kulesi - Muhafız Avlusu',
                    'author' => 'Muhafız Yüzbaşısı Torin',
                    'author_role' => 'Kale Güvenliği ve Asayiş Şefi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/3.png',
                    'is_breaking' => false,
                    'description' => "Lonca Hazinedarı Lord Vance, çalışma odasında bütçe kayıtlarını incelerken pencereyi delen zehirli bir tatar yayı okuyla hedef alındı! Vance kıl payı kurtulurken, saldırganın geride bıraktığı parşömende 'Kasa açılacak, bedel ödenecek' yazdığı bildirildi.",
                    'full_story' => "Valiant Shade Hazine Dairesi dün gece kanlı bir suikast girişimine sahne oldu. Saat 01:20 sularında, lonca surlarının doğu kulesinden ateşlenen gölge işlemeli zehirli bir arbalet oku, Lonca Hazinedarı Lord Vance'in tam kulağının yanından geçerek arkasındaki meşe kitaplığa saplandı.

Okun ucunda sürülen zehrin nadir bulunan 'Wyvern Nefesi' toksini olduğu simyacılar tarafından doğrulandı. Tek bir sıyrığın bile dakikalar içinde kalbi durdurmaya yeteceği bu ölümcül sıvının karaborsadan temin edildiği düşünülüyor.

Suikastçının kaçarken düşürdüğü siyah ipek mendilde, Valiant Shade'in rakip hanedanlarına işaret eden gizli bir kabartma mühür ve kısa bir not bulundu: 'Kasa açılacak, bedel kanla ödenecek.'

Muhafız Yüzbaşısı Torin kalede kırmızı alarm ilan etti; Hazine Kulesi'ne giriş çıkışlar kısıtlandı ve tüm lonca üyelerine şüpheli kişileri derhal bildirmeleri çağrısında bulunuldu.",
                    'testimony' => "Ok meşe tahtaya girdiğinde havaya yeşil bir duman yükseldi. Hedef doğrudan boynumdu. Loncamızın içindeki köstebek bulunana dek kimseye huzur yok. — Lord Vance",
                    'stats' => [
                        ['label' => 'Hedef', 'value' => 'Lord Vance', 'color' => 'text-white'],
                        ['label' => 'Kullanılan Zehir', 'value' => 'Wyvern Nefesi', 'color' => 'text-danger'],
                        ['label' => 'Alarm Seviyesi', 'value' => 'Kod Kırmızı', 'color' => 'text-danger'],
                        ['label' => 'Kelle Ödülü', 'value' => '2.500 Altın', 'color' => 'text-warning'],
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
                    'title' => "Ejderha Geçidi Şehidi Baş Büyücü Lyra için Büyük Şapel'de Görkemli Cenaze Töreni",
                    'category' => 'Ölüm & Anma',
                    'badge_color' => 'danger',
                    'category_icon' => 'icon-base ti tabler-flame-off',
                    'tag' => 'Şapel Töreni & Veda',
                    'tag_icon' => 'icon-base ti tabler-ribbon-health',
                    'date_text' => '2 Gün Önce',
                    'published_date' => '29 Eylül 2026, 14:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_heroes_funeral.jpg',
                    'location_badge' => 'Gök Tapınağı & Şehitler Meydanı',
                    'author' => 'Yüksek Patrik Aurelius',
                    'author_role' => 'Güneş Şapeli Baş Rahibi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/4.png',
                    'is_breaking' => false,
                    'description' => "Ejderha Geçidi Seferi'nde birliğini korumak için kendini alev hortumunun önüne siper eden Baş Büyücü Lyra Stormchaser, yüzlerce lonca üyesi ve halkın katıldığı görkemli bir törenle sonsuzluğa uğurlandı.",
                    'full_story' => "Şehrin sokakları dün sabah erken saatlerden itibaren siyah bayraklar ve beyaz zambaklarla donatıldı. Ejderha Geçidi'nde alevler saçan Kırmızı Ejderha'nın ateş dalgasını 'Yıldız Kalkanı' büyüsüyle durduran ve 14 gencecik lonca üyesinin hayatını kurtarırken bedenini feda eden Baş Büyücü Lyra Stormchaser için veda günüydü.

Gök Tapınağı'ndan Şehitler Meydanı'na kadar uzanan kortejde, loncanın en kıdemli 12 paladini Lyra'nın asasını ve büyücü cüppesini taşıdı. Lonca Ustası, Lyra'ya ölümünden sonra 'Valiant Shade Üstün Cesaret ve Fedakarlık Madalyası' takdim etti.

Lyra'nın vasiyeti üzerine lonca kütüphanesine bağışladığı kadim büyü parşömenleri korumaya alınırken, adı Lonca Meclis Salonu'nun girişindeki Altın Mermer Kitabe'ye kazındı.",
                    'testimony' => "Alevler üstümüze gelirken tek bir adım bile geri çekilmedi. 'Kaçın!' dedi, 'Ben ışığı tutarım'. O ışık hiç sönmeyecek. — Çırak Büyücü Selene",
                    'stats' => [
                        ['label' => 'Kurtardığı Can', 'value' => '14 Üye', 'color' => 'text-success'],
                        ['label' => 'Tören Katılımı', 'value' => '800+ Kişi', 'color' => 'text-info'],
                        ['label' => 'Liyakat Nişanı', 'value' => 'Altın Madalya', 'color' => 'text-warning'],
                        ['label' => 'Anıt Kabri', 'value' => 'Büyücüler Bahçesi', 'color' => 'text-primary'],
                    ],
                    'button_text' => 'Anma Kitabesini Gör',
                    'button_url' => '/valiant-shade/pages/news#news-4',
                    'button_color' => 'danger',
                    'button_icon' => 'icon-base ti tabler-flower',
                    'order' => 4,
                    'is_active' => true,
                    'views_count' => 1740,
                ],
                [
                    'id' => 5,
                    'title' => 'Simyacılar Çarşısı Kaynıyor: Sahte Diriliş ve Mana İksirleri Piyasada mı?',
                    'category' => 'Dedikodu & Uyarı',
                    'badge_color' => 'warning',
                    'category_icon' => 'icon-base ti tabler-flask',
                    'tag' => 'Çarşı Teftişi & Skandal',
                    'tag_icon' => 'icon-base ti tabler-search',
                    'date_text' => 'Dün 18:40',
                    'published_date' => '30 Eylül 2026, 18:40',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_arcane_academy.jpg',
                    'location_badge' => 'Aşağı Şehir Simyacılar Çıkmazı',
                    'author' => 'Usta Simyacı Althea',
                    'author_role' => 'Lonca Teftiş Heyeti Temsilcisi',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/5.png',
                    'is_breaking' => false,
                    'description' => "Son seferden dönen maceracıların iksir şişelerinde beklenen şifa yerine sadece şekerli su ve parlayan yosun tozu bulduğu iddiası üzerine lonca teftiş müfettişleri çarşıdaki 3 dükkanı ani baskınla mühürledi.",
                    'full_story' => "Son günlerde zindan görevlerinden dönen savaşçıların ardı ardına sağlık ocağına başvurması üzerine korkunç bir skandal patlak verdi. Zindan canavarlarıyla çarpışırken tüketilen 'Büyük Can İksirleri'nin yaraları iyileştirmek bir yana, savaşçıların görüşünü bulanıklaştırdığı ve mide spazmlarına yol açtığı ortaya çıktı!

Lonca Teftiş Heyeti, Aşağı Şehir'deki üç simya atölyesine gece baskını düzenledi. Ele geçirilen yüzlerce şişenin mühürlerinin sahte olduğu, içeriğinde mandrake kökü yerine ucuz bataklık mantarları ve mavi gıda boyası kullanıldığı tespit edildi.

İddialara göre sahtekarlık şebekesi, lonca armalı kutuları kopyalayarak ucuz fiyatla acemi maceracılara pazarlıyordu. Sahte iksirleri satan iki tüccar Lonca Zindanı'na kapatıldı; lonca hekimleri tüm üyelere yalnızca Lonca Resmi Eczanesi'nden iksir tedarik etmeleri yönünde sert bir uyarı yayınladı.",
                    'testimony' => "Can havliyle iksiri kafaya diktiğimde şifa yerine ağzımdan mavi köpükler çıktı! Neredeyse goblinlerin elinde can verecektim. — Savaşçı Dan",
                    'stats' => [
                        ['label' => 'Mühürlenen Dükkan', 'value' => '3 Atölye', 'color' => 'text-danger'],
                        ['label' => 'El Konulan Şişe', 'value' => '420 Adet', 'color' => 'text-warning'],
                        ['label' => 'Etkilenen Üye', 'value' => '12 Maceracı', 'color' => 'text-info'],
                        ['label' => 'Hapis Cezası', 'value' => 'Müebbet Zindan', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Sahte İksir Listesi',
                    'button_url' => '/valiant-shade/pages/news#news-5',
                    'button_color' => 'warning',
                    'button_icon' => 'icon-base ti tabler-alert-circle',
                    'order' => 5,
                    'is_active' => true,
                    'views_count' => 1310,
                ],
                [
                    'id' => 6,
                    'title' => 'Kadim Ejderha Ignis Dize Getirildi: Lonca Hazinesine 14.500 Altın ve Kadim Eserler Eklendi',
                    'category' => 'Sefer Zaferi',
                    'badge_color' => 'success',
                    'category_icon' => 'icon-base ti tabler-trophy',
                    'tag' => 'Efsanevi Zafer',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '3 Gün Önce',
                    'published_date' => '28 Eylül 2026, 11:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_dragon_quest.jpg',
                    'location_badge' => 'Ejderha Zirvesi Harabeleri',
                    'author' => 'General Ragnor',
                    'author_role' => '1. Saldırı Lejyonu Başkomutanı',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/6.png',
                    'is_breaking' => false,
                    'description' => "3 hafta süren amansız kuşatmanın ardından Kadim Alev Ejderhası Ignis mağlup edildi. Ejderhanın ininden çıkarılan Antik Hazine loncamıza taşındı; sefere katılan tüm birliklere yüklü prim dağıtılıyor.",
                    'full_story' => "Valiant Shade'in en tecrübeli 30 elit savaşçısı, haftalardır Ejderha Zirvesi'nin dondurucu zirvelerinde kamp kurarak Kadim Ignis'in inini abluka altında tutuyordu. Dün sabah şafak sökerken başlayan nihai taarruzda, ejderhanın alev menzili büyücülerimizin dondurucu fırtınasıyla kırıldı.

General Ragnor'un bizzat vurduğu son darbeyle dev yaratık yere serildi. Mağara derinliklerinden çıkarılan ganimetler arasında; Antik Krallık'tan kalma 14.500 altın sikke, 6 adet efsanevi rünik silah ve lonca kütüphanesini aydınlatacak 'Güneş Taşı' yer alıyor.

Zafer kutlamaları bu akşam Lonca Meclis Salonu'nda fıçıların patlatılmasıyla başlayacak!",
                    'testimony' => "Kalkanımın üzerine ateş püskürttüğünde kemiklerimin eridiğini hissettim ama bir santim bile geri adım atmadık. Bu zafer tüm Valiant Shade ailesinindir! — General Ragnor",
                    'stats' => [
                        ['label' => 'Kazanılan Altın', 'value' => '+14.500 GP', 'color' => 'text-warning'],
                        ['label' => 'Kazanılan XP', 'value' => '+8.500 XP', 'color' => 'text-success'],
                        ['label' => 'Rünik Eser', 'value' => '6 Kadim Silah', 'color' => 'text-info'],
                        ['label' => 'Kayıp Durumu', 'value' => '0 Can Kaybı', 'color' => 'text-success'],
                    ],
                    'button_text' => 'Ganimet Paylaşımını Gör',
                    'button_url' => '/valiant-shade/pages/news#news-6',
                    'button_color' => 'success',
                    'button_icon' => 'icon-base ti tabler-coin',
                    'order' => 6,
                    'is_active' => true,
                    'views_count' => 3120,
                ],
                [
                    'id' => 7,
                    'title' => "Yüksek Divan Kararı: 'Kızıl Maskeli İsyancılar' İçin Canlı ya da Ölü 5.000 Altın Kelle Ödülü",
                    'category' => 'Lonca Meclisi',
                    'badge_color' => 'primary',
                    'category_icon' => 'icon-base ti tabler-gavel',
                    'tag' => 'Resmi Ferman',
                    'tag_icon' => 'icon-base ti tabler-file-certificate',
                    'date_text' => '4 Gün Önce',
                    'published_date' => '27 Eylül 2026, 16:30',
                    'read_time' => '2 dk okuma',
                    'image_file' => 'guild_hall_council.jpg',
                    'location_badge' => 'Valiant Shade Yüksek Divan Salonu',
                    'author' => 'Şansölye Valen',
                    'author_role' => 'Yüksek Adalet ve Divan Başkanı',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/7.png',
                    'is_breaking' => false,
                    'description' => "Loncanın kervan yollarını basıp erzak konvoylarını yağmalayan Kızıl Maske çetesine karşı Yüksek Divan'dan tarihi ferman çıktı: Elebaşı 'Tek Gözlü Silas' ve kurmaylarının başına rekor kelle ödülü kondu.",
                    'full_story' => "Valiant Shade Yüksek Divanı, son 1 ay içinde Kuzey Ticaret Yolu üzerinde vuku bulan 5 kervan baskınını değerlendirmek üzere olağanüstü toplandı.

Kervan muhafızlarından 3 yoldaşımızın şehit olması ve lonca demirhanesine giden 2 araba dolusu mithril cevherinin çalınması bardağı taşıran son damla oldu. Şansölye Valen imzalı fermana göre:

1. Kızıl Maskeli çetenin elebaşı 'Tek Gözlü Silas'ın kellesine 5.000 Altın,
2. Teğmenlerinin her biri için 1.500 Altın,
3. Çalınan mithril cevherini getiren birliğe cevherin değerinin %30'u peşin ikramiye ödenecektir.

Tüm lonca paralı askerleri ve serbest kılıçlar bu fermandan yararlanabilir.",
                    'testimony' => "Ticaret yollarımızı tehdit eden hiçbir eşkıya cezasız kalmayacak. Adalet ya kılıçla ya da iple tecelli edecektir. — Şansölye Valen",
                    'stats' => [
                        ['label' => 'Baş Ödülü', 'value' => '5.000 Altın', 'color' => 'text-warning'],
                        ['label' => 'Çete Gücü', 'value' => '~40 Haydut', 'color' => 'text-danger'],
                        ['label' => 'Operasyon Bölgesi', 'value' => 'Kuzey Geçitleri', 'color' => 'text-info'],
                        ['label' => 'Yetki Durumu', 'value' => 'Canlı veya Ölü', 'color' => 'text-white'],
                    ],
                    'button_text' => 'Fermanı İmzala & Katıl',
                    'button_url' => '/valiant-shade/pages/news#news-7',
                    'button_color' => 'primary',
                    'button_icon' => 'icon-base ti tabler-scroll',
                    'order' => 7,
                    'is_active' => true,
                    'views_count' => 1650,
                ],
                [
                    'id' => 8,
                    'title' => "Obsidyen Hisar'da Uyanış: Gökyüzünde Kırmızı Ay ve Çığlık Sesleri",
                    'category' => 'Gizem & Kehanet',
                    'badge_color' => 'dark',
                    'category_icon' => 'icon-base ti tabler-moon',
                    'tag' => 'Karanlık Kehanet',
                    'tag_icon' => 'icon-base ti tabler-sparkles',
                    'date_text' => '5 Gün Önce',
                    'published_date' => '26 Eylül 2026, 03:00',
                    'read_time' => '3 dk okuma',
                    'image_file' => 'guild_shadow_realm.jpg',
                    'location_badge' => 'Yasak Gölge Vadisi Sınırı',
                    'author' => 'Kahin Morvath',
                    'author_role' => 'Yıldız Gözlemevi Baş Astroloğu',
                    'author_avatar' => 'static/valiant-shade/assets/img/avatars/8.png',
                    'is_breaking' => false,
                    'description' => "Yüzyıllardır mühürlü duran Obsidyen Hisar'ın kulelerinden gökyüzüne kan kırmızısı bir ışık sütunu yükseldi. Çevredeki köylüler dağlardan gelen zincir şakırtıları ve çığlık sesleri nedeniyle köylerini boşaltıyor.",
                    'full_story' => "Gölge Vadisi sınırındaki gözlem kulelerimiz dün gece dehşet verici doğaüstü olaylar kaydetti. Gece yarısı saat tam 03:00'te ay kırmızıya büründü ve Obsidyen Hisar'ın tepe noktasından bulutları delen karanlık bir enerji dalgası yayıldı.

Gözlemciler, hisarın çatlaklarından akan mor alevleri ve taş duvarlara çarpan ruhani haykırışları raporladı. Astrologlar heyeti, bu olayın 300 yıl önce hapsedilen 'Gölge Hükümdarı Malakor'un mührünün zayıfladığına dair açık bir işaret olduğunu vurguluyor.

Lonca Ustası, bölgeye deneyimli izci birlikleri gönderilmesi ve sınır karakollarının tahkim edilmesi talimatını verdi.",
                    'testimony' => "Rüzgar artık dağ kokmuyor, kükürt ve bin yıllık mezar toprağı kokuyor. Mühür kırılırsa dünya karanlığa boğulur. — Kahin Morvath",
                    'stats' => [
                        ['label' => 'Tehdit Seviyesi', 'value' => 'Omega Sınıfı', 'color' => 'text-danger'],
                        ['label' => 'Gözlem Noktası', 'value' => 'Kuzey Kulesi', 'color' => 'text-warning'],
                        ['label' => 'Tahliye', 'value' => '2 Köy Boşaldı', 'color' => 'text-info'],
                        ['label' => 'Mühür Durumu', 'value' => 'Kritik Çatlak', 'color' => 'text-danger'],
                    ],
                    'button_text' => 'Kehanet Parşömenini Oku',
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
                    'testimony' => "With his dying breath, Braum slammed his shield down and roared: 'Stop covering my back, get the apprentices out!' Because of him, we breathe today. — Ranger Finn, Survivor",
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
                    'testimony' => "I saw the clasp beneath the cloak with my own eyes—the eagle crest of the High Council. They didn't even clink mugs; took the coin and dissolved into the dark. — Dwarf Thorbjorn",
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
                    'testimony' => "Green smoke hissed from the wood where the bolt struck. The target was my throat. There will be no peace until the traitor within is exposed. — Lord Vance",
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
                    'testimony' => "As the flames rushed down, she didn't yield an inch. 'Fly!' she yelled, 'I will hold the light'. That light shall never fade. — Apprentice Mage Selene",
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
                    'testimony' => "In dire peril I quaffed the flask, and blue froth erupted from my mouth instead of mend! I nearly fell to goblins. — Dan the Fighter",
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
                    'testimony' => "When fire washed across my shield my marrow burned, yet we gave not an inch. This victory belongs to all Valiant Shade! — General Ragnor",
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
                    'testimony' => "No outlaw menacing our lifelines shall evade retribution. Justice shall be done by blade or noose. — Chancellor Valen",
                    'stats' => [
                        ['label' => 'Bounty Head', 'value' => '5,000 Gold', 'color' => 'text-warning'],
                        ['label' => 'Gang Strength', 'value' => '~40 Bandits', 'color' => 'text-danger'],
                        ['label' => 'Operation Area', 'value' => 'North Passes', 'color' => 'text-info'],
                        ['label' => 'Authority', 'value' => 'Dead or Alive', 'color' => 'text-white'],
                    ],
                    'button_text' => 'Sign Decree & Join',
                    'button_url' => '/valiant-shade/pages/news#news-7',
                    'button_color' => 'primary',
                    'button_icon' => 'icon-base ti tabler-scroll',
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
                    'testimony' => "The wind no longer smells of stone, but of brimstone and centuries of sepulchral earth. If the seal shatters, darkness consumes all. — Prophet Morvath",
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
            ->orderBy('order')
            ->orderByDesc('created_at')
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
        $deathNewsCount = $allNews->filter(fn($n) => in_array((int)$n->id, [1, 6], true) || stripos($n->category, 'Ölüm') !== false || stripos($n->category, 'Death') !== false)->count();
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
            'activeCategory' => $activeCategory,
            'searchQuery' => $searchQuery,
            'usesDatabaseNews' => $dbNews->isNotEmpty(),
            ...$management,
        ]);
    }

    /**
     * @return array{canManageNews: bool, canUpdateNews: bool, canDeleteNews: bool}
     */
    protected function newsManagementFlags(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'canManageNews' => false,
                'canUpdateNews' => false,
                'canDeleteNews' => false,
            ];
        }

        if ($user->isAdmin() || $user->hasPermission('archives.all') || $user->isArchivistBailiff()) {
            return [
                'canManageNews' => true,
                'canUpdateNews' => true,
                'canDeleteNews' => true,
            ];
        }

        $canUpdate = $user->hasPermission('archives.update') || $user->hasPermission('archives.create');
        $canDelete = $user->hasPermission('archives.delete');

        return [
            'canManageNews' => $canUpdate || $canDelete,
            'canUpdateNews' => $canUpdate,
            'canDeleteNews' => $canDelete,
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

                return $row;
            }

            $row = (array) $item;
            if (! isset($row['image_asset']) && ! empty($row['image_url'])) {
                $row['image_asset'] = asset(ltrim((string) $row['image_url'], '/'));
            }
            $row['is_database'] = in_array((int) ($row['id'] ?? 0), $databaseIds, true);

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
        } elseif (!empty($request->image_preset)) {
            $imageUrl = $request->image_preset;
        } elseif (!empty($request->image_url)) {
            $imageUrl = $request->image_url;
        }

        $news->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'badge_color' => $validated['badge_color'] ?? $news->badge_color,
            'category_icon' => $validated['category_icon'] ?? $news->category_icon,
            'tag' => $validated['tag'] ?? null,
            'tag_icon' => $validated['tag_icon'] ?? $news->tag_icon,
            'date_text' => $validated['date_text'] ?? null,
            'description' => $validated['description'],
            'image_url' => $imageUrl,
            'stats' => $validated['stats'] ?? $news->stats,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'button_color' => $validated['button_color'] ?? $news->button_color,
            'button_icon' => $validated['button_icon'] ?? $news->button_icon,
            'location_badge' => $validated['location_badge'] ?? null,
            'order' => $validated['order'] ?? $news->order,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : $news->is_active,
        ]);

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
     * Delete news record.
     */
    public function destroy(int $id, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeNewsManagement('delete');

        $news = News::findOrFail($id);
        app(GazetteIssueService::class)->releaseNewsFromGazette($news);
        $news->delete();

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

        $message = 'Bu gazete sayısı kilitli; haber durumu değiştirilemez.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $redirectRoute = str_contains($request->path(), 'guild-news') ? 'valiant-shade.pages-guild-news' : 'valiant-shade.pages-news';

        return redirect()->route($redirectRoute)->with('error', $message);
    }
}

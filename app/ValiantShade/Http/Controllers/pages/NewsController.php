<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
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
     * Get the master static news dataset.
     */
    public static function getNewsData(): Collection
    {
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
        $allNews = self::getNewsData();

        // Optional category filtering
        $activeCategory = $request->query('category', 'all');
        $searchQuery = trim((string) $request->query('q', ''));

        $filteredNews = $allNews;

        if ($activeCategory !== 'all') {
            $filteredNews = $filteredNews->filter(function ($item) use ($activeCategory) {
                return stripos($item->category, $activeCategory) !== false;
            });
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

        // Highlights & Counts
        $featuredNews = $allNews->firstWhere('is_breaking', true) ?? $allNews->first();
        $deathNewsCount = $allNews->filter(fn($n) => stripos($n->category, 'Ölüm') !== false)->count();
        $rumorNewsCount = $allNews->filter(fn($n) => stripos($n->category, 'Dedikodu') !== false)->count();
        $victoryNewsCount = $allNews->filter(fn($n) => stripos($n->category, 'Zafer') !== false)->count();
        $decreeNewsCount = $allNews->filter(fn($n) => stripos($n->category, 'Meclis') !== false || stripos($n->category, 'Ferman') !== false)->count();

        $statsSummary = [
            'total' => $allNews->count(),
            'deaths' => $deathNewsCount,
            'rumors' => $rumorNewsCount,
            'victories' => $victoryNewsCount,
            'decrees' => $decreeNewsCount,
        ];

        return view('valiant-shade.content.pages.pages-news', [
            'news' => $filteredNews->values(),
            'allNews' => $allNews,
            'featuredNews' => $featuredNews,
            'statsSummary' => $statsSummary,
            'activeCategory' => $activeCategory,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Show single news item as JSON (for detail reader modal / API).
     */
    public function show(int $id, Request $request): JsonResponse|View
    {
        $newsItem = self::getNewsData()->firstWhere('id', $id);

        if (!$newsItem) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Haber bulunamadı'], 404);
            }
            return redirect()->route('valiant-shade.pages-news')->with('error', 'Haber bulunamadı.');
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
     * Graceful fallback stubs for routes.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Haber eklendi (Simülasyon).'])
            : redirect()->route('valiant-shade.pages-news')->with('success', 'Haber kaydedildi.');
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Haber güncellendi (Simülasyon).'])
            : redirect()->route('valiant-shade.pages-news')->with('success', 'Haber güncellendi.');
    }

    public function destroy(int $id, Request $request): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Haber silindi (Simülasyon).'])
            : redirect()->route('valiant-shade.pages-news')->with('success', 'Haber silindi.');
    }

    public function toggleActive(int $id, Request $request): RedirectResponse|JsonResponse
    {
        return response()->json(['success' => true, 'is_active' => true, 'message' => 'Durum güncellendi.']);
    }

    public function resetDefaults(Request $request): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Varsayılan bültenler yüklendi.'])
            : redirect()->route('valiant-shade.pages-news')->with('success', 'Varsayılan bültenler yüklendi.');
    }
}

<?php

namespace App\ValiantShade\Services;

use App\Models\PlayerXp;
use Illuminate\Support\Collection;

class QuestReportService
{
    /**
     * Get all custom reports created by users.
     */
    public function getCustomReports(): array
    {
        $path = storage_path('app/custom_quest_reports.json');
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Merge custom reports with raw master sheet reports.
     */
    public function getAllReportsData(): array
    {
        $custom = $this->getCustomReports();
        $raw = $this->getRawReportsData();
        return array_merge($custom, $raw);
    }

    /**
     * Store a newly created quest report.
     */
    public function createReport(array $data): array
    {
        $custom = $this->getCustomReports();
        $all = $this->getAllReportsData();
        $nextId = count($all) > 0 ? max(array_column($all, 'id')) + 1 : 1;

        $party = $data['party'] ?? [];
        if (!is_array($party)) {
            $party = [];
        }

        // Calculate dynamic average level from party
        $levels = !empty($party) ? array_column($party, 'level') : [1];
        $numericLevels = array_map('floatval', $levels);
        $avgLevel = count($numericLevels) > 0 ? round(array_sum($numericLevels) / count($numericLevels), 2) : 1.0;

        $isMain = !empty($data['is_main']) && ($data['is_main'] === true || $data['is_main'] === '1' || $data['is_main'] === 'main' || $data['is_main'] === 'epic');
        $tierType = $data['tier_type'] ?? ($isMain ? 'main' : 'side');

        $totalXp = (int) ($data['total_xp'] ?? 750);
        $playerCount = count($party) > 0 ? count($party) : (int) ($data['player_count'] ?? 1);
        $guildCut = (float) ($data['guild_cut'] ?? round($totalXp * 0.05, 2));
        $vaultCut = (float) ($data['vault_cut'] ?? 0);
        $avgReward = $playerCount > 0 ? round(($totalXp - $guildCut - $vaultCut) / $playerCount, 2) : $totalXp;

        $newReport = [
            'id' => $nextId,
            'title' => $data['title'] ?? 'Yeni Görev Raporu',
            'date' => $data['date'] ?? date('d.m.Y'),
            'is_main' => $isMain,
            'main_marker' => $tierType === 'epic' ? '✓✓' : ($isMain ? '✓' : 'x'),
            'tier_type' => $tierType,
            'status' => $data['status'] ?? 'success',
            'level' => (int) round($avgLevel),
            'calculated_avg_level' => $avgLevel,
            'total_xp' => $totalXp,
            'guild_cut' => $guildCut,
            'depot_cut' => (float) ($data['depot_cut'] ?? 0),
            'vault_cut' => $vaultCut,
            'extra_reward' => (float) ($data['extra_reward'] ?? 0),
            'avg_reward' => $avgReward,
            'player_count' => $playerCount,
            'gm' => $data['gm'] ?? 'Shalvaz',
            'reporter' => $data['reporter'] ?? ($party[0]['name'] ?? 'Kahraman'),
            'location' => $data['location'] ?? 'Gölge Diyar',
            'enemies' => $data['enemies'] ?? 'Bilinmeyen Düşmanlar',
            'report_summary' => $data['report_summary'] ?? 'Görev başarıyla icra edildi.',
            'full_report' => $data['full_report'] ?? ($data['report_summary'] ?? 'Detaylı operasyon raporu hazırlandı.'),
            'party' => $party,
        ];

        array_unshift($custom, $newReport);
        file_put_contents(storage_path('app/custom_quest_reports.json'), json_encode($custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $newReport;
    }

    /**
     * Get all quest reports with optional filtering.
     */
    public function getReports(array $filters = []): Collection
    {
        $reports = collect($this->getAllReportsData());

        // Calculate dynamic average level for each quest based on party members
        $reports = $reports->map(function ($q) {
            if (!empty($q['party'])) {
                $levels = array_column($q['party'], 'level');
                $q['calculated_avg_level'] = count($levels) > 0 ? round(array_sum($levels) / count($levels), 2) : (float) $q['level'];
            } else {
                $q['calculated_avg_level'] = (float) $q['level'];
            }
            return $q;
        });

        // Filter by Type (Main vs Side)
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            if ($filters['type'] === 'main') {
                $reports = $reports->filter(fn($q) => $q['is_main'] === true);
            } elseif ($filters['type'] === 'side') {
                $reports = $reports->filter(fn($q) => $q['is_main'] === false);
            }
        }

        // Filter by GM
        if (!empty($filters['gm']) && $filters['gm'] !== 'all') {
            $gm = mb_strtolower($filters['gm']);
            $reports = $reports->filter(fn($q) => mb_strtolower($q['gm'] ?? '') === $gm);
        }

        // Filter by Recommended Level
        if (!empty($filters['level']) && $filters['level'] !== 'all') {
            $lvl = (int) $filters['level'];
            $reports = $reports->filter(fn($q) => (int) $q['level'] === $lvl);
        }

        // Search term
        if (!empty($filters['search'])) {
            $term = mb_strtolower($filters['search']);
            $reports = $reports->filter(function ($q) use ($term) {
                if (str_contains(mb_strtolower($q['title']), $term)) return true;
                if (str_contains(mb_strtolower($q['gm'] ?? ''), $term)) return true;
                if (str_contains(mb_strtolower($q['reporter'] ?? ''), $term)) return true;
                if (str_contains(mb_strtolower($q['location'] ?? ''), $term)) return true;
                if (str_contains(mb_strtolower($q['report_summary'] ?? ''), $term)) return true;

                // Check party members
                foreach ($q['party'] ?? [] as $member) {
                    if (str_contains(mb_strtolower($member['name'] ?? ''), $term)) return true;
                }
                return false;
            });
        }

        return $reports->values();
    }

    /**
     * Get a single report by ID.
     */
    public function getReportById(int|string $id): ?array
    {
        $report = collect($this->getAllReportsData())->firstWhere('id', (int) $id);
        if ($report) {
            if (!empty($report['party'])) {
                $levels = array_column($report['party'], 'level');
                $report['calculated_avg_level'] = count($levels) > 0 ? round(array_sum($levels) / count($levels), 2) : (float) $report['level'];
            } else {
                $report['calculated_avg_level'] = (float) $report['level'];
            }
        }
        return $report;
    }

    /**
     * Compute summary statistics for dashboard header cards.
     */
    public function getStatistics(): array
    {
        $all = collect($this->getAllReportsData());

        $totalQuests = $all->count();
        $mainQuests = $all->where('is_main', true)->count();
        $sideQuests = $all->where('is_main', false)->count();
        $totalXp = $all->sum('total_xp');
        $totalVaultCut = $all->sum('vault_cut');
        $totalGuildCut = $all->sum('guild_cut');

        // All calculated average levels
        $allAvgLevels = [];
        foreach ($all as $q) {
            $levels = !empty($q['party']) ? array_column($q['party'], 'level') : [$q['level']];
            $allAvgLevels[] = count($levels) > 0 ? array_sum($levels) / count($levels) : $q['level'];
        }
        $overallAvgLevel = count($allAvgLevels) > 0 ? round(array_sum($allAvgLevels) / count($allAvgLevels), 2) : 1.0;

        // GM Distribution
        $gmCounts = $all->groupBy('gm')->map->count()->sortDesc()->toArray();
        $topGm = key($gmCounts) ?? 'Bart';

        return [
            'total_quests' => $totalQuests,
            'main_quests' => $mainQuests,
            'side_quests' => $sideQuests,
            'total_xp' => $totalXp,
            'total_vault_sp' => $totalVaultCut,
            'total_guild_cut' => $totalGuildCut,
            'overall_avg_level' => $overallAvgLevel,
            'avg_level' => $overallAvgLevel,
            'top_gm' => $topGm,
            'gm_counts' => $gmCounts,
        ];
    }

    /**
     * Get distinct GM list.
     */
    public function getDistinctGms(): array
    {
        return collect($this->getAllReportsData())
            ->pluck('gm')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Raw master sheet data extracted directly from the user's Görev Raporları spreadsheet tab.
     */
    public function getRawReportsData(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Kasadaki Para',
                'date' => '08.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 10,
                'extra_reward' => 0,
                'avg_reward' => 175.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tolrun',
                'location' => 'Siwion Ticaret Bölgesi',
                'enemies' => 'Şehir Hırsızları, Rüşvetçi Muhafızlar',
                'report_summary' => 'Lonca finans ambarına sızmaya çalışan yerel şebekenin kasayı soyma girişimi engellendi. Kaçırılmak üzere olan 10 SP lonca kasasına iade edildi.',
                'full_report' => "Operasyon Özeti: Gece yarısı saatlerinde Siwion alt ticaret mahallesinde şüpheli hareketlilik raporlandı. Tolrun önderliğindeki ekip, eski lonca deposunun arka girişine kurulan pusuyu fark etti. Kısa bir arbedenin ardından hırsız çetesi etkisiz hale getirildi. Kasada bulunan değerli senetler ve 10 SP tutarındaki fon kurtarılarak lonca kasasına teslim edildi. Katılımcı kahramanlar disiplinli ve hızlı refleksleriyle takdir topladı.",
                'party' => [
                    ['name' => 'Tolrun', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 175],
                    ['name' => 'Allorion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 2, 'xp' => 175],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 175],
                    ['name' => 'Shanter', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 175],
                ],
            ],
            [
                'id' => 2,
                'title' => 'Aile Yadigarlarının Peşinde',
                'date' => '15.08.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 142.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Leothar',
                'location' => 'Kuzey Siwion Mahzenleri',
                'enemies' => 'Mezar Yağmacıları, Zombi Muhafızlar',
                'report_summary' => 'Aristokrat ailenin çalınan kadim hançeri ve mühür yüzüğü terk edilmiş mahzenlerden çıkarıldı.',
                'full_report' => "Görev Günlüğü: Kadim soylulardan Lord Cassian'ın atalarına ait yadigar sandığı, gizli bir tarikat tarafından yağmalanmıştı. Leothar, Darren, Veigar, Willhard ve Caselgo'dan oluşan beş kişilik keşif birliği mahzen tünellerine girdi. Çatışmada mezar hırsızları bertaraf edildi, yadigar başarıyla aileye teslim edilerek loncamıza yüksek itibar kazandırıldı.",
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 2, 'xp' => 142],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 142],
                    ['name' => 'Darren', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 142],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 2, 'xp' => 142],
                    ['name' => 'Caselgo', 'role' => 'Member', 'class' => 'Bard', 'level' => 1, 'xp' => 142],
                ],
            ],
            [
                'id' => 3,
                'title' => 'Siwion Paladinleri Soruşturması',
                'date' => '17.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 178.13,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => "Rock'naar",
                'location' => 'Işık Tapınağı Surları',
                'enemies' => 'Sapkın Muhafızlar, Casuslar',
                'report_summary' => 'Paladin kışlasında çıkan yetki çatışması ve rüşvet iddiaları yerinde araştırıldı.',
                'full_report' => "Soruşturma Tutanağı: Tapınak muhafızları arasındaki huzursuzluk loncamıza iletildiğinde Rock'naar ve ekibi kışlaya intikal etti. Şüpheli mektuplar ele geçirildi ve tapınağın içine sızmış olan yabancı istihbarat ajanları deşifre edildi.",
                'party' => [
                    ['name' => "Rock'naar", 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 178],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 178],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 2, 'xp' => 178],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 178],
                ],
            ],
            [
                'id' => 4,
                'title' => 'Harken Ayaklanması Pt-1',
                'date' => '22.08.2026',
                'is_main' => true,
                'main_marker' => '✓✓',
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'guild_cut' => 112.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Averon Felt',
                'location' => 'Harken Vadisi Girişi',
                'enemies' => 'İsyancı Paralı Askerler, Kuşatma Birlikleri',
                'report_summary' => 'Harken Dükalığı genelinde başlayan isyanın ilk cephesi bastırıldı, köprü kontrol altına alındı.',
                'full_report' => "Destansı Harekât Raporu (Bölüm 1): Harken bölgesindeki genel isyan bayrağı çekildiğinde Valiant Shade acil seferberlik ilan etti. Averon Felt kumandasındaki 6 kahramanlık öncü taburu vadi köprüsünü tutarak isyancı takviye birliklerini püskürttü.",
                'party' => [
                    ['name' => 'Averon Felt', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 2, 'xp' => 356],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Elindel', 'role' => 'Member', 'class' => 'Wizard', 'level' => 2, 'xp' => 356],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 356],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 2, 'xp' => 356],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 5,
                'title' => 'Harken Ayaklanması Pt-2',
                'date' => '22.08.2026',
                'is_main' => true,
                'main_marker' => '✓✓',
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'guild_cut' => 112.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Shalvaz',
                'reporter' => 'Rindark',
                'location' => 'Harken Kale Meydanı',
                'enemies' => 'İsyancı Süvariler, Ağır Zırhlı Şövalyeler',
                'report_summary' => 'Harken garnizonu kurtarıldı ve kale kapıları isyancı kuşatmasından temizlendi.',
                'full_report' => "Destansı Harekât Raporu (Bölüm 2): İkinci tabur kale surlarına yönelik topçu ve koçbaşı taarruzunu durdurmak üzere kalabalık isyancı kanadına girdi. Rindark, Rufus, Tamokul, Ugly, Veigar ve Vostea ortak manevrayla isyancı bayraktarını düşürdü.",
                'party' => [
                    ['name' => 'Rindark', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Rufus', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 356],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 356],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 356],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 356],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 6,
                'title' => 'Harken Ayaklanması Pt-3',
                'date' => '22.08.2026',
                'is_main' => true,
                'main_marker' => '✓✓',
                'tier_type' => 'epic',
                'level' => 1,
                'total_xp' => 2250,
                'guild_cut' => 112.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 356.25,
                'player_count' => 6,
                'gm' => 'Vain',
                'reporter' => 'Willhard',
                'location' => 'Igor Harken Sığınağı',
                'enemies' => 'İsyancı Elebaşı Igor Harken, Gölge Muhafızlar',
                'report_summary' => 'İsyanın beyni Igor Harken kıstırıldı ve teslim alındı. İsyanın arkasındaki finansal kaynaklar ele geçirildi.',
                'full_report' => "Destansı Harekât Raporu (Bölüm 3): Willhard komutasındaki üçüncü darbe timi sığınağa baskın düzenledi. Igor Harken ağır yaralanarak canlı ele geçirildi ve yargılanmak üzere Siwion Lonca Zindanlarına sevk edildi.",
                'party' => [
                    ['name' => 'Willhard', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 2, 'xp' => 356],
                    ['name' => 'Allorion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 2, 'xp' => 356],
                    ['name' => 'Skadmir', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 356],
                    ['name' => "Rock'naar", 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 356],
                    ['name' => 'Ogden', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 356],
                    ['name' => 'Shanter', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 356],
                ],
            ],
            [
                'id' => 7,
                'title' => 'Temsilcinin Kolyesi',
                'date' => '27.08.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Elindel',
                'location' => 'Büyükelçilik Konutu',
                'enemies' => 'Suikastçılar, Hırsız Loncası Casusları',
                'report_summary' => 'Kraliyet temsilcisinden çalınan büyü korumalı kolye kurtarıldı. Lonca kasasına 50 SP aktarıldı.',
                'full_report' => "Görev Raporu: Diplomatik kriz yaratabilecek hırsızlık vakasında Elindel ve arkadaşları ipuçlarını takip ederek liman depolarındaki kaçakçı teknesine baskın yaptı. Kolye sağlam olarak geri getirildi.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 165],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 165],
                ],
            ],
            [
                'id' => 8,
                'title' => 'Bernard Vefalı Tehlikede Mi?',
                'date' => '28.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Nicholas',
                'location' => 'Bernard Vefalı Konağı',
                'enemies' => 'Şantajcılar, Zehirbazlar',
                'report_summary' => 'Şehir tüccarı Bernard Vefalı’ya kurulan zehirleme komplosu önlendi.',
                'full_report' => "Operasyon Detayı: Nicholas liderliğindeki ekip tüccarın konağında nöbet tuttu. Aşçı kılığına giren suikastçı yemek servisi sırasında yakalandı.",
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 132],
                    ['name' => 'Marcus', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 132],
                    ['name' => 'Tolrun', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 132],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 132],
                ],
            ],
            [
                'id' => 9,
                'title' => 'Igor Harken Duruşması',
                'date' => '29.08.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 118.75,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Hall',
                'location' => 'Yüksek Adalet Divanı',
                'enemies' => 'İsyancı İntikam Timi, Kaçırma Girişimi',
                'report_summary' => 'Mahkeme binası korundu, Igor Harken’ın infaz kararı onaylandı.',
                'full_report' => "Duruşma Güvenlik Raporu: Mahkeme salonuna patlayıcılarla girmeye çalışan isyancı artıkları salon kapısında Hall ve ekibi tarafından durduruldu. Mahkeme güven içinde tamamlandı.",
                'party' => [
                    ['name' => 'Hall', 'role' => 'Reporter', 'class' => 'Cleric', 'level' => 1, 'xp' => 118],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 118],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 2, 'xp' => 118],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 118],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 118],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 118],
                ],
            ],
            [
                'id' => 10,
                'title' => 'Landon Çiftlikleri Gizemi',
                'date' => '29.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'location' => 'Landon Tarım Ovaları',
                'enemies' => 'Korkuluk Golemleri, Çılgın Simyacı',
                'report_summary' => 'Ekinleri yok eden ve çiftçileri korkutan canlandırılmış kuklalar yok edildi.',
                'full_report' => "Saha Raporu: Tamokul'un balyozu ekinliklerde hareket eden yapay saman yaratıkları parçaladı. Gizli bir simya laboratuvarı mühürlenerek 50 SP loncaya aktarıldı.",
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Aelinor', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 165],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 165],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 165],
                ],
            ],
            [
                'id' => 11,
                'title' => 'Igor Harken Suikast Soruşturması',
                'date' => '30.08.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Vostea',
                'location' => 'Siwion Yüksek Zindanı',
                'enemies' => 'Zindan Muhafızı Hainler, Suikastçılar',
                'report_summary' => 'Hücresinde öldürülen Igor Harken cinayetinin izleri takip edildi.',
                'full_report' => "İstihbarat Raporu: İnfazdan bir gece önce Igor Harken hücresinde ölü bulundu. Vostea'nın iz takibi cinayetin içeriden bir muhafız desteğiyle işlendiğini ortaya çıkardı.",
                'party' => [
                    ['name' => 'Vostea', 'role' => 'Reporter', 'class' => 'Druid', 'level' => 1, 'xp' => 165],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 165],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 165],
                ],
            ],
            [
                'id' => 12,
                'title' => 'Yellowburn Hanı Soruşturması Pt-1',
                'date' => '30.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 150,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 128.13,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Ran',
                'location' => 'Yellowburn Hanı',
                'enemies' => 'Karanlık Tüccarlar, Yasadışı Bahisçiler',
                'report_summary' => 'Handa yürütülen yasadışı bahis ve zehir ticareti basıldı. Depoya 150 malzeme, kasaya 50 SP kazandırıldı.',
                'full_report' => "Baskın Raporu: Hanın gizli mahzeninde kurulan horoz dövüşü ve kaçak simya deposuna baskın yapıldı. Çok miktarda hammadde lonca deposuna çekildi.",
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 1, 'xp' => 128],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 128],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 128],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 128],
                ],
            ],
            [
                'id' => 13,
                'title' => 'Görev Raporu (Haftalık Devriye)',
                'date' => '31.08.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Buskall',
                'location' => 'Siwion Batı Ormanları',
                'enemies' => 'Vahşi Yaban Domuzları, Orman Eşkıyaları',
                'report_summary' => 'Ticaret yolundaki eşkıya kampları temizlendi ve yol tüccarlar için tekrar güvenli kılındı.',
                'full_report' => "Devriye Tutanağı: Buskall önderliğinde orman sınırında pusu kuran 8 kişilik eşkıya grubu dağıtıldı, rehin alınan yük arabası kurtarıldı.",
                'party' => [
                    ['name' => 'Buskall', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 165],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 165],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 165],
                ],
            ],
            [
                'id' => 14,
                'title' => 'Ayaklanmadan Kalanlar',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Shalvaz',
                'reporter' => 'Nicholas',
                'location' => 'Harken Kırsalı',
                'enemies' => 'Kaçak İsyancılar, Yağmacılar',
                'report_summary' => 'İsyandan sonra köyleri basan firari çeteler yakalandı.',
                'full_report' => "Saha Raporu: Firari askerlerin köylülerin erzak ambarlarına el koyduğu bilgisi üzerine Nicholas, Ran, Vostea, Will ve Tolrun köye yetişti. Çete etkisiz kılındı.",
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 132],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 132],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 132],
                    ['name' => 'Will', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Tolrun', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                ],
            ],
            [
                'id' => 15,
                'title' => 'Demircinin İstekleri',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 220.83,
                'player_count' => 3,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'location' => 'Demir Sırt Madenleri',
                'enemies' => 'Maden Trolleri, Kaya Yarasaları',
                'report_summary' => 'Lonca demircisi için nadir göktaşı madeni damarı bulundu ve güvenceye alındı.',
                'full_report' => "Maden Keşfi: Tamokul, Veigar ve Rock'naar madenin en derin galerilerine inerek göktaşı parçalarını çıkardı.",
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 220],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 220],
                    ['name' => "Rock'naar", 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 220],
                ],
            ],
            [
                'id' => 16,
                'title' => 'Dolandırıcıların Peşinde',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'location' => 'Aşağı Pazar Yeri',
                'enemies' => 'Sahte İksir Tacirleri, Kumarbazlar',
                'report_summary' => 'Halkı sahte şifa iksirleriyle kandıran dolandırıcı loncası çökertildi.',
                'full_report' => "Şehir Güvenlik Raporu: Elindel ve ekibi sahte iksir atölyesini bastı, zehirli karışımlar imha edildi ve 50 SP ceza fonu tahsil edildi.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 132],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 132],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Allorion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 2, 'xp' => 132],
                    ['name' => 'Ethelrinda', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 132],
                ],
            ],
            [
                'id' => 17,
                'title' => 'Gnoll Tehlikesi',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Vain',
                'reporter' => 'Leothar',
                'location' => 'Kuru Vadi Geçidi',
                'enemies' => 'Gnoll Savaşçıları, Sırtlan Sürüleri',
                'report_summary' => 'Vadiyi kesen Gnoll sürüsü mağlup edildi, kervan yolu kurtarıldı.',
                'full_report' => "Muharebe Raporu: Leothar ve Willhard ön safta kalkan duvarı örerek Gnoll taarruzunu kırdı. Nyreen ve Rindark kanatlardan vurarak sürüyü dağıttı.",
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 2, 'xp' => 132],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 132],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 2, 'xp' => 132],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 2, 'xp' => 132],
                ],
            ],
            [
                'id' => 18,
                'title' => 'Leyla <3 Sorin (Aşk Mektupları)',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'location' => 'Gül Bahçeleri & Asil Malikane',
                'enemies' => 'Kıskanç Muhafızlar, Casus Kargalar',
                'report_summary' => 'İki düşman asil ailenin genç aşıkları arasındaki gizli mektuplar ulaştırıldı.',
                'full_report' => "Gizli Görev Raporu: Siyasi gerginlik içerisindeki iki hanedanın gençleri arasındaki irtibat Elindel ve Nyreen'in yanıltma büyüleriyle sağlandı.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 165],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 2, 'xp' => 165],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Rufus', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 165],
                ],
            ],
            [
                'id' => 19,
                'title' => 'Liman Grevi',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Aelinor',
                'location' => 'Siwion Ana Limanı',
                'enemies' => 'Kışkırtıcı Ajanlar, Liman Zorbaları',
                'report_summary' => 'Liman işçilerini provoke ederek isyana sürüklemek isteyen ajanlar yakalandı.',
                'full_report' => "Arabuluculuk & Güvenlik Raporu: Aelinor işçi sendikasıyla diplomasi yürütürken, araya karışmış yabancı provokatörler Allorion ve Leothar tarafından yakalandı. Liman tekrar çalışmaya başladı.",
                'party' => [
                    ['name' => 'Aelinor', 'role' => 'Reporter', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 132],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 132],
                    ['name' => 'Nyreen', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 2, 'xp' => 132],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 132],
                    ['name' => 'Allorion', 'role' => 'Member', 'class' => 'Ranger', 'level' => 2, 'xp' => 132],
                ],
            ],
            [
                'id' => 20,
                'title' => 'Şifalı Bitkiler',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Elindel',
                'location' => 'Sisli Bataklık',
                'enemies' => 'Zehirli Sarmaşıklar, Bataklık Yılanları',
                'report_summary' => 'Veba salgınına karşı gereken Gümüş Çiçeği kökleri toplandı.',
                'full_report' => "Doğa Keşif Raporu: Bataklığın derinliklerinde yetişen nadir kökler Elindel ve Buskall tarafından canavarlarla çatışılarak toplandı. Lonca revirine şifa stoku sağlandı.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 165],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 165],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 165],
                ],
            ],
            [
                'id' => 21,
                'title' => 'Sirkte Garip Gece',
                'date' => '01.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => "Rock'naar",
                'location' => 'Gezgin Sirk Çadırı',
                'enemies' => 'Büyülenmiş Vahşi Canavarlar, İllüzyonist',
                'report_summary' => 'Sirk çadırında hipnotize edilerek halka saldırtılan aslanlar yatıştırıldı.',
                'full_report' => "Olay Müdahale Raporu: Rock'naar ve Will gösteri sırasında sahneye fırlayarak yırtıcıları sakinleştirdi. Seyircilerden hiçbiri zarar görmeden sirk müdürü tutuklandı.",
                'party' => [
                    ['name' => "Rock'naar", 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 165],
                    ['name' => 'Will', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                ],
            ],
            [
                'id' => 22,
                'title' => "Viktor'u Kurtarma",
                'date' => '01.09.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 0,
                'avg_reward' => 142.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Ran',
                'location' => 'Haydut Kanyonu',
                'enemies' => 'Fidye Çetesi, Kurt Sürüleri',
                'report_summary' => 'Kaçırılan lonca irtibat subayı Viktor sağ salim kurtarıldı.',
                'full_report' => "Kurtarma Operasyonu: Kanyon içindeki mağarada esir tutulan Viktor, Ran ve Rindark'ın sessiz sızma taktiğiyle kurtarıldı. Çete reisi Marcus ve Nicholas tarafından tutuklandı.",
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 1, 'xp' => 142],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 142],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 142],
                    ['name' => 'Marcus', 'role' => 'Member', 'class' => 'Paladin', 'level' => 1, 'xp' => 142],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 142],
                ],
            ],
            [
                'id' => 23,
                'title' => 'Ebon Wings Geri Döndü!',
                'date' => '02.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Nicholas',
                'location' => 'Kuzey Dağ Zirvesi',
                'enemies' => 'Dev Kuzgunlar, Gökyüzü Yağmacıları',
                'report_summary' => 'Dağ yollarındaki gözetleme kulelerini taciz eden efsanevi Ebon Wings kuşatıldı.',
                'full_report' => "Hava Taarruzu Raporu: Nicholas ve Samih gözetleme kulesine konuşlandı. Ran'in ok yağmuru yaratıkları geri çekilmeye zorladı.",
                'party' => [
                    ['name' => 'Nicholas', 'role' => 'Reporter', 'class' => 'Rogue', 'level' => 1, 'xp' => 165],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 165],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 165],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 165],
                ],
            ],
            [
                'id' => 24,
                'title' => 'Potion Bulma',
                'date' => '03.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Bart',
                'reporter' => 'Fangor',
                'location' => 'Batık Kervan Kalıntıları',
                'enemies' => 'Bataklık Balıkları, Zehirli Sümüklüböcekler',
                'report_summary' => 'Bataklığa gömülen simya kervanının sağlam kalan iksir kasaları çıkarıldı.',
                'full_report' => "İntikal Raporu: Fangor ve Averon Felt sular altındaki araba enkazını kaldırdı. 12 şişe şifa ve kudret iksiri lonca deposuna kazandırıldı.",
                'party' => [
                    ['name' => 'Fangor', 'role' => 'Reporter', 'class' => 'Warlock', 'level' => 1, 'xp' => 165],
                    ['name' => 'Richard', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 165],
                    ['name' => 'Averon Felt', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 165],
                ],
            ],
            [
                'id' => 25,
                'title' => 'Liman Çetelerini Soruşturma Sq-1',
                'date' => '05.09.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 2,
                'total_xp' => 1000,
                'guild_cut' => 50,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 150,
                'avg_reward' => 210.00,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Elindel',
                'location' => 'Gölge İskele Bölgesi',
                'enemies' => 'Gölge Çetesi Elebaşları, Bıçakçılar',
                'report_summary' => 'Limanı haraca bağlayan çetenin ana hücresi çökertildi. Ekstra 150 Gold ödül kazanıldı.',
                'full_report' => "Seviye 2 Harekât Raporu: Elindel ve Nedasp çetenin kumarhanesine eşzamanlı baskın yaptı. Çete kasasından 150 Gold ekstra ganimet ve 50 SP lonca fonu ele geçirildi.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 210],
                    ['name' => 'Nedasp', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 210],
                    ['name' => 'Hall', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 210],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 210],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 210],
                ],
            ],
            [
                'id' => 26,
                'title' => 'Liman Çetelerini Soruşturma Sq-2',
                'date' => '05.09.2026',
                'is_main' => true,
                'main_marker' => '✓',
                'tier_type' => 'main',
                'level' => 2,
                'total_xp' => 1000,
                'guild_cut' => 50,
                'depot_cut' => 0,
                'vault_cut' => 0,
                'extra_reward' => 150,
                'avg_reward' => 275.00,
                'player_count' => 4,
                'gm' => 'Vain',
                'reporter' => 'Ran',
                'location' => 'Batık Gemi Tersanesi',
                'enemies' => 'Kaçak Silah Tüccarları, Çete Fedaileri',
                'report_summary' => 'Çetenin deniz yoluyla kaçmaya çalışan ikinci hücresi kuşatıldı ve teslim alındı.',
                'full_report' => "Seviye 2 Harekât Raporu (Kısım 2): Ran, Ulrich, Vostea ve Ugly tersanede demirli fırkateyne gizlice sızdı. Kaçak barut ve silah dolu kargo lonca kontrolüne geçti.",
                'party' => [
                    ['name' => 'Ran', 'role' => 'Reporter', 'class' => 'Ranger', 'level' => 1, 'xp' => 275],
                    ['name' => 'Ulrich', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 275],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 275],
                    ['name' => 'Ugly', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 275],
                ],
            ],
            [
                'id' => 27,
                'title' => 'Nonlethal Silah Yaptırma',
                'date' => '08.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Bart',
                'reporter' => 'Buskall',
                'location' => 'Lonca Atölyeleri',
                'enemies' => 'Eğitim Golemleri, Test Mankeni Kontrol Büyüleri',
                'report_summary' => 'Şehir içi operasyonlarda kan dökülmeden şüphelileri yakalamak için bayıltıcı cop ve ağ silahları geliştirildi.',
                'full_report' => "Atölye Test Raporu: Buskall ve ekibi prototip bayıltıcı sopaları test etti. 20 adet öldürücü olmayan teçhizat lonca envanterine girdi.",
                'party' => [
                    ['name' => 'Buskall', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Bruk', 'role' => 'Member', 'class' => 'Monk', 'level' => 1, 'xp' => 132],
                    ['name' => 'Tamokul', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 1, 'xp' => 132],
                    ['name' => 'Willhard', 'role' => 'Member', 'class' => 'Barbarian', 'level' => 2, 'xp' => 132],
                    ['name' => 'Aelinor', 'role' => 'Member', 'class' => 'Sorcerer', 'level' => 1, 'xp' => 132],
                ],
            ],
            [
                'id' => 28,
                'title' => 'Kayıp Vatandaşlar Hakkında Pt-1',
                'date' => '11.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 75,
                'extra_reward' => 0,
                'avg_reward' => 212.50,
                'player_count' => 3,
                'gm' => 'Bart',
                'reporter' => 'Leothar',
                'location' => 'Kanalizasyon Labirentleri',
                'enemies' => 'Kanal Ghoulları, Veba Sıçanları',
                'report_summary' => 'Şehir altından kaybolan 3 masum vatandaş kurtarıldı. Kasaya 75 SP bağışlandı.',
                'full_report' => "Arama Kurtarma Raporu: Leothar kutsal ışığıyla kanalizasyonu aydınlatırken, Shalvaz ve Nicholas yaralı vatandaşlara ilk müdahaleyi yaptı. Tehlikeli bir tarikat izi tespit edildi.",
                'party' => [
                    ['name' => 'Leothar', 'role' => 'Reporter', 'class' => 'Paladin', 'level' => 2, 'xp' => 212],
                    ['name' => 'Shalvaz', 'role' => 'Member', 'class' => 'Cleric', 'level' => 1, 'xp' => 212],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 212],
                ],
            ],
            [
                'id' => 29,
                'title' => 'Haritacının Çalınan Malları',
                'date' => '12.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 110.42,
                'player_count' => 6,
                'gm' => 'Bart',
                'reporter' => 'Nedasp',
                'location' => 'Doğu Patikası Harabe Kulesi',
                'enemies' => 'Haydut Okçuları, Yaban Tazıları',
                'report_summary' => 'Krallığın gizli sınır haritaları ve pusulaları haydut kampından geri alındı.',
                'full_report' => "Geri Alma Harekâtı: Nedasp, Ran, Vostea, Ulrich, Vain ve Leothar haritacının kaçırılan belgelerini kuleye tırmanarak geri aldı.",
                'party' => [
                    ['name' => 'Nedasp', 'role' => 'Reporter', 'class' => 'Fighter', 'level' => 2, 'xp' => 110],
                    ['name' => 'Ran', 'role' => 'Member', 'class' => 'Ranger', 'level' => 1, 'xp' => 110],
                    ['name' => 'Vostea', 'role' => 'Member', 'class' => 'Druid', 'level' => 1, 'xp' => 110],
                    ['name' => 'Ulrich', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 110],
                    ['name' => 'Vain', 'role' => 'Member', 'class' => 'Warlock', 'level' => 2, 'xp' => 110],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 110],
                ],
            ],
            [
                'id' => 30,
                'title' => 'Liman Soruşturması Pt-1',
                'date' => '13.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 220.83,
                'player_count' => 3,
                'gm' => 'Shalvaz',
                'reporter' => 'Hall',
                'location' => 'Eski Gümrük Binası',
                'enemies' => 'Kaçak Mallar Muhafızları, Sahte Memurlar',
                'report_summary' => 'Gümrükte mühür bozup sahte vergi toplayan şebeke ortaya çıkarıldı.',
                'full_report' => "Denetim Tutanağı: Hall, Bart ve Drednar gümrük ambarına baskın yaptı. Sahte defterler delil olarak mahkemeye sunuldu.",
                'party' => [
                    ['name' => 'Hall', 'role' => 'Reporter', 'class' => 'Cleric', 'level' => 1, 'xp' => 220],
                    ['name' => 'Bart', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 220],
                    ['name' => 'Drednar', 'role' => 'Member', 'class' => 'Fighter', 'level' => 2, 'xp' => 220],
                ],
            ],
            [
                'id' => 31,
                'title' => 'Maaş Teslimatı',
                'date' => '14.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 132.50,
                'player_count' => 5,
                'gm' => 'Drednar',
                'reporter' => 'Elindel',
                'location' => 'Sınır Muhafız Karakolu',
                'enemies' => 'Pusu Kuran Çeteler, Yaban Kurtları',
                'report_summary' => 'Sınır karakoluna gönderilen asker maaş sandığı salimen ulaştırıldı.',
                'full_report' => "Lojistik Güvenlik Raporu: Ağır zırhlı kervan arabası orman boğazında pusuya düşürüldü. Elindel'in alev büyüleri ve Buskall'ın kalkanı sayesinde tek bir sikke bile kaybolmadan karakola varıldı.",
                'party' => [
                    ['name' => 'Elindel', 'role' => 'Reporter', 'class' => 'Wizard', 'level' => 2, 'xp' => 132],
                    ['name' => 'Veigar', 'role' => 'Member', 'class' => 'Wizard', 'level' => 1, 'xp' => 132],
                    ['name' => 'Nicholas', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 132],
                    ['name' => 'Buskall', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 132],
                    ['name' => 'Leothar', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 132],
                ],
            ],
            [
                'id' => 32,
                'title' => 'Mahkum Transferi',
                'date' => '15.09.2026',
                'is_main' => false,
                'main_marker' => 'x',
                'tier_type' => 'side',
                'level' => 1,
                'total_xp' => 750,
                'guild_cut' => 37.5,
                'depot_cut' => 0,
                'vault_cut' => 50,
                'extra_reward' => 0,
                'avg_reward' => 165.63,
                'player_count' => 4,
                'gm' => 'Shalvaz',
                'reporter' => 'Tamokul',
                'location' => 'Siwion - Yüksek Hisar Yolu',
                'enemies' => 'Mahkum Kurtarma Çetesi, Zehirli Okçular',
                'report_summary' => 'Tehlikeli büyücü mahkum yüksek güvenlikli hisara teslim edildi.',
                'full_report' => "Transfer Raporu: Tamokul, Rindark, Samih ve Lagnard mahkumu taşıyan demir kafesi korudu. Yolda düzenlenen 2 ayrı kurtarma teşebbüsü püskürtüldü ve mahkum hisar muhafızlarına teslim edildi.",
                'party' => [
                    ['name' => 'Tamokul', 'role' => 'Reporter', 'class' => 'Barbarian', 'level' => 1, 'xp' => 165],
                    ['name' => 'Rindark', 'role' => 'Member', 'class' => 'Fighter', 'level' => 1, 'xp' => 165],
                    ['name' => 'Samih', 'role' => 'Member', 'class' => 'Rogue', 'level' => 1, 'xp' => 165],
                    ['name' => 'Lagnard', 'role' => 'Member', 'class' => 'Paladin', 'level' => 2, 'xp' => 165],
                ],
            ],
        ];
    }
}

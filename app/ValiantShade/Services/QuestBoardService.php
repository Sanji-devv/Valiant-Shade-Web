<?php

namespace App\ValiantShade\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class QuestBoardService
{
    /**
     * Storage path for the Quest Board dataset.
     */
    protected function getStoragePath(): string
    {
        return storage_path('app/quest_board.json');
    }

    /**
     * Ensure the storage file is initialized with the master Görev Listesi quests.
     */
    public function ensureInitialized(): void
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            $this->resetToDefaults();
        }
    }

    /**
     * Reset quest board dataset to the master Görev Listesi default records.
     */
    public function resetToDefaults(): array
    {
        $raw = $this->getRawQuestData();
        $this->saveQuestsData($raw);
        return $raw;
    }

    /**
     * Clear cached quests dataset (Cache Invalidation).
     */
    public function clearCache(): void
    {
        Cache::forget('valiant_shade.quests.raw');
    }

    /**
     * Get all raw quest array items from Cache with JSON storage fallback (Cache-Aside Pattern).
     */
    public function getAllQuestsRaw(): array
    {
        return Cache::remember('valiant_shade.quests.raw', 3600, function () {
            $this->ensureInitialized();
            $path = $this->getStoragePath();
            if (file_exists($path)) {
                $fp = fopen($path, 'rb');
                if ($fp) {
                    flock($fp, LOCK_SH);
                    $json = stream_get_contents($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    if (!empty($json)) {
                        $decoded = json_decode($json, true);
                        if (is_array($decoded) && !empty($decoded)) {
                            return $decoded;
                        }
                    }
                }
            }
            return $this->getRawQuestData();
        });
    }

    /**
     * Persist quests array to storage with Atomic Exclusive File Locking (LOCK_EX) and Cache Invalidation.
     */
    protected function saveQuestsData(array $quests): void
    {
        $path = $this->getStoragePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode(array_values($quests), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tempPath = $path . '.' . uniqid('lock_', true) . '.tmp';

        $fp = fopen($tempPath, 'wb');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $json);
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                rename($tempPath, $path);
            } else {
                fclose($fp);
                @unlink($tempPath);
                file_put_contents($path, $json, LOCK_EX);
            }
        } else {
            file_put_contents($path, $json, LOCK_EX);
        }

        $this->clearCache();
    }

    /**
     * Get a specific quest by ID.
     */
    public function getQuestById(int|string $id): ?array
    {
        $quests = $this->getAllQuestsRaw();
        foreach ($quests as $q) {
            if ((int) $q['id'] === (int) $id) {
                return $q;
            }
        }
        return null;
    }

    /**
     * Create and pin a new quest parchment to the board.
     */
    public function createQuest(array $data): array
    {
        $quests = $this->getAllQuestsRaw();

        $maxId = 0;
        foreach ($quests as $q) {
            if (isset($q['id']) && (int) $q['id'] > $maxId) {
                $maxId = (int) $q['id'];
            }
        }
        $newId = $maxId + 1;

        $category = $data['category'] ?? 'side';
        $categoryLabel = match($category) {
            'main' => 'Main Quest',
            'character' => 'Member Request',
            'recurring' => 'Recurring',
            default => 'Side Quest',
        };

        $eligKey = $data['eligibility'] ?? 'all_ranks';
        $eligLabel = match($eligKey) {
            'all_ranks' => 'All Ranks',
            'candidate_member' => 'Candidate / Member',
            'member_senior' => 'Member / Senior',
            'senior_expert' => 'Senior / Expert',
            'expert' => 'Expert',
            default => ($data['eligibility_label'] ?? $eligKey),
        };

        $sealColor = match($category) {
            'main' => 'red',
            'character' => 'bronze',
            'recurring' => 'blue',
            default => 'gold',
        };

        $newQuest = [
            'id' => $newId,
            'category' => $category,
            'category_label' => $categoryLabel,
            'title' => trim($data['title']),
            'eligibility_key' => $eligKey,
            'eligibility' => $eligLabel,
            'character' => !empty($data['character']) ? trim($data['character']) : 'Guild',
            'status' => $data['status'] ?? 'active',
            'priority' => $data['priority'] ?? ($category === 'main' ? 'high' : 'medium'),
            'seal_color' => $data['seal_color'] ?? $sealColor,
            'description' => trim($data['description'] ?? ''),
            'location' => !empty($data['location']) ? trim($data['location']) : 'Unknown',
            'reward' => !empty($data['reward']) ? trim($data['reward']) : 'Standard Reward',
            'created_at' => now()->toIso8601String(),
        ];

        // Newest quest at the beginning of list
        array_unshift($quests, $newQuest);
        $this->saveQuestsData($quests);

        return $newQuest;
    }

    /**
     * Update an existing quest decree.
     */
    public function updateQuest(int|string $id, array $data): ?array
    {
        $quests = $this->getAllQuestsRaw();
        $foundIndex = null;

        foreach ($quests as $idx => $q) {
            if ((int) $q['id'] === (int) $id) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex === null) {
            return null;
        }

        $current = $quests[$foundIndex];

        if (isset($data['category'])) {
            $current['category'] = $data['category'];
            $current['category_label'] = match($data['category']) {
                'main' => 'Main Quest',
                'character' => 'Member Request',
                'recurring' => 'Recurring',
                default => 'Side Quest',
            };
            $current['seal_color'] = match($data['category']) {
                'main' => 'red',
                'character' => 'bronze',
                'recurring' => 'blue',
                default => 'gold',
            };
        }

        if (isset($data['eligibility'])) {
            $eligKey = $data['eligibility'];
            $current['eligibility_key'] = $eligKey;
            $current['eligibility'] = match($eligKey) {
                'all_ranks' => 'All Ranks',
                'candidate_member' => 'Candidate / Member',
                'member_senior' => 'Member / Senior',
                'senior_expert' => 'Senior / Expert',
                'expert' => 'Expert',
                default => ($data['eligibility_label'] ?? $eligKey),
            };
        }

        if (isset($data['title'])) $current['title'] = trim($data['title']);
        if (isset($data['character'])) $current['character'] = trim($data['character']);
        if (isset($data['location'])) $current['location'] = trim($data['location']);
        if (isset($data['reward'])) $current['reward'] = trim($data['reward']);
        if (isset($data['description'])) $current['description'] = trim($data['description']);
        if (isset($data['status'])) $current['status'] = $data['status'];
        if (isset($data['seal_color'])) $current['seal_color'] = $data['seal_color'];
        if (isset($data['priority'])) $current['priority'] = $data['priority'];

        $current['updated_at'] = now()->toIso8601String();

        $quests[$foundIndex] = $current;
        $this->saveQuestsData($quests);

        return $current;
    }

    /**
     * Update quest status (active, on_hold, completed, cancelled).
     */
    public function updateStatus(int|string $id, string $status): ?array
    {
        return $this->updateQuest($id, ['status' => $status]);
    }

    /**
     * Delete quest decree permanently from board.
     */
    public function deleteQuest(int|string $id): bool
    {
        $quests = $this->getAllQuestsRaw();
        $filtered = array_filter($quests, fn($q) => (int) $q['id'] !== (int) $id);

        if (count($filtered) === count($quests)) {
            return false;
        }

        $this->saveQuestsData(array_values($filtered));
        return true;
    }

    /**
     * Get all quests from the Master Sheet Görev Listesi tab.
     * Fully persistent and richly decorated for the Medieval Quest Board.
     */
    public function getAllQuests(array $filters = []): Collection
    {
        $quests = collect($this->getAllQuestsRaw());

        // Filter by category
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $category = $filters['category'];
            $quests = $quests->filter(function ($q) use ($category) {
                if ($category === 'character') {
                    return !empty($q['character']) && $q['character'] !== 'Guild' && $q['character'] !== 'Lonca';
                }
                return $q['category'] === $category;
            });
        }

        // Filter by eligibility (Uygunluk)
        if (!empty($filters['eligibility']) && $filters['eligibility'] !== 'all') {
            $eligibility = $filters['eligibility'];
            $quests = $quests->filter(fn($q) => 
                ($q['eligibility_key'] ?? '') === $eligibility 
                || $q['eligibility'] === $eligibility
            );
        }

        // Filter by status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status = $filters['status'];
            $quests = $quests->filter(fn($q) => $q['status'] === $status);
        }

        // Search
        if (!empty($filters['search'])) {
            $term = mb_strtolower($filters['search']);
            $quests = $quests->filter(function ($q) use ($term) {
                return str_contains(mb_strtolower($q['title']), $term)
                    || str_contains(mb_strtolower($q['character'] ?? ''), $term)
                    || str_contains(mb_strtolower($q['eligibility']), $term)
                    || str_contains(mb_strtolower($q['description']), $term);
            });
        }

        return $quests->values();
    }

    /**
     * Get Guild Demographics from Columns K, L in Görev Listesi.
     */
    public function getGuildInfo(): array
    {
        $isTr = app()->getLocale() === 'tr';

        return [
            'ranks' => [
                ['rank' => $isTr ? 'Uzman' : 'Expert',       'count' => 22,  'badge' => 'bg-label-success'],
                ['rank' => $isTr ? 'Kıdemli' : 'Senior',     'count' => 22,  'badge' => 'bg-label-warning'],
                ['rank' => $isTr ? 'Üye' : 'Member',         'count' => 18,  'badge' => 'bg-label-primary'],
                ['rank' => $isTr ? 'Aday' : 'Candidate',     'count' => 273, 'badge' => 'bg-label-secondary'],
                ['rank' => 'Clerk',                          'count' => 60,  'badge' => 'bg-label-info'],
                ['rank' => $isTr ? 'Gezgin' : 'Wanderer',    'count' => 9,   'badge' => 'bg-label-dark'],
                ['rank' => $isTr ? 'Inaktif' : 'Inactive',   'count' => 214, 'badge' => 'bg-label-danger'],
            ],
            'total_adventurers' => 618,
            'status_legend' => [
                ['color' => 'success', 'name' => $isTr ? 'Aktif' : 'Active', 'desc' => $isTr ? 'Maceracı kabul eden açık görevler' : 'Open quests accepting adventurers'],
                ['color' => 'danger',  'name' => $isTr ? 'Kırmızı (Görev Bitti)' : 'Red (Completed)', 'desc' => $isTr ? 'Tamamlanmış ve arşive kaldırılan görevler' : 'Completed and archived quests'],
                ['color' => 'info',    'name' => $isTr ? 'Mavi/Mor (Görev Askıda)' : 'Blue/Purple (On Hold)', 'desc' => $isTr ? 'İkinci bir emre kadar askıya alınanlar' : 'Suspended until further notice'],
                ['color' => 'secondary','name' => $isTr ? 'Gri (Görev İptal)' : 'Gray (Cancelled)',   'desc' => $isTr ? 'İptal edilen lonca talepleri' : 'Cancelled guild requests'],
            ],
        ];
    }

    /**
     * Master dataset derived directly from the Görev Listesi tab.
     * English by default; Turkish if locale is 'tr'.
     */
    protected function getRawQuestData(): array
    {
        $isTr = app()->getLocale() === 'tr';

        return [
            // ================= 1. MAIN QUESTS =================
            [
                'id' => 1,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Adalar Krallığı\'nın Talebi' : 'Request of the Isles Kingdom',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr 
                    ? 'Adalar Krallığı heyeti loncamıza acil resmi yardım talebinde bulundu. Yüksek tehlike derecesi nedeniyle sadece Kıdemli ve Uzman maceracılar başvurabilir.'
                    : 'The delegation of the Isles Kingdom has submitted an urgent official request for aid. Due to high danger, only Senior and Expert adventurers may apply.',
                'location' => $isTr ? 'Adalar Denizi / Kraliyet Limanı' : 'Isles Sea / Royal Harbor',
                'reward' => $isTr ? '15,000 XP & Yüksek Krallık Ödeneği' : '15,000 XP & Royal Treasury Grant',
            ],
            [
                'id' => 2,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Aldelenle arayı kapatmak' : 'Reconciling with Aldelen',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Aldelen yerleşkesi ile olan diplomatik ve ticari ilişkilerin yeniden tesisi için her rütbeden maceracının katılabileceği genel lonca operasyonu.'
                    : 'General guild operation open to all ranks to restore diplomatic and trade relations with the Aldelen settlement.',
                'location' => $isTr ? 'Aldelen Vadisi' : 'Aldelen Valley',
                'reward' => $isTr ? '4,500 XP & İtibar Puanı' : '4,500 XP & Reputation Points',
            ],
            [
                'id' => 3,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Beyfendi Görevi' : 'The Gentleman\'s Commission',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Gizemli soylu "Beyfendi" namına yürütülen derin araştırma ve infaz operasyonu.'
                    : 'Deep investigation and elimination operation conducted on behalf of the mysterious noble known as "The Gentleman".',
                'location' => $isTr ? 'Yukarı Şehir Mahzenleri' : 'Upper City Vaults',
                'reward' => $isTr ? '12,000 XP & Büyülü Eşya' : '12,000 XP & Enchanted Relic',
            ],
            [
                'id' => 4,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Gökmetali Edinmek' : 'Procuring Skymetal',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Lonca demircilerinin efsanevi zırh ve silah dövebilmesi için düşen meteor bölgesinden saf gökmetali (skymetal) çıkarma görevi.'
                    : 'Extraction of pure skymetal from the fallen meteor impact site to allow master smiths to forge legendary equipment.',
                'location' => $isTr ? 'Krater Tepeleri' : 'Crater Ridge',
                'reward' => $isTr ? '18,000 XP & Gökmetali Silah Parçası' : '18,000 XP & Skymetal Weapon Component',
            ],
            [
                'id' => 5,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Lagrima Keşfi' : 'Expedition to Lagrima',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'on_hold',
                'priority' => 'medium',
                'seal_color' => 'blue',
                'description' => $isTr
                    ? 'Lagrima kadim harabelerinin haritalandırılması ve keşfi. Güvenlik önlemleri gereği geçici olarak askıya alınmıştır.'
                    : 'Mapping and surveying the ancient ruins of Lagrima. Temporarily suspended due to high security hazards.',
                'location' => $isTr ? 'Lagrima Harabeleri' : 'Lagrima Ruins',
                'reward' => $isTr ? '6,000 XP & Arkeolojik Eserler' : '6,000 XP & Archaeological Relics',
            ],
            [
                'id' => 6,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Legrimadaki Dev' : 'The Giant of Legrima',
                'eligibility_key' => 'expert',
                'eligibility' => $isTr ? 'Uzman' : 'Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'on_hold',
                'priority' => 'high',
                'seal_color' => 'blue',
                'description' => $isTr
                    ? 'Legrima geçidini tutan antik tepe devinin etkisiz hale getirilmesi. Bölgedeki sismik hareketler nedeniyle görev askıdadır.'
                    : 'Neutralizing the ancient mountain giant blocking the Legrima Pass. Suspended due to seismic tremors.',
                'location' => $isTr ? 'Legrima Geçidi' : 'Legrima Pass',
                'reward' => $isTr ? '25,000 XP & Dev Kemik Asası' : '25,000 XP & Giant Bone Staff',
            ],
            [
                'id' => 7,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Leydi Amaranth\'ın Talebi' : 'Lady Amaranth\'s Request',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Leydi Amaranth\'ın şatosu çevresinde görülen şüpheli gölgelerin tespiti ve leydinin şahsi korumasının sağlanması.'
                    : 'Identifying suspicious shadow intruders around Lady Amaranth\'s chateau and securing her private guard.',
                'location' => $isTr ? 'Amaranth Malikanesi' : 'Amaranth Estate',
                'reward' => $isTr ? '5,000 XP & 500 GP' : '5,000 XP & 500 GP',
            ],
            [
                'id' => 8,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Nexari Hanesi Araştırması' : 'House Nexari Investigation',
                'eligibility_key' => 'expert',
                'eligibility' => $isTr ? 'Uzman' : 'Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Kayıp Nexari hanedanının gizli kütüphanesine sızarak yasaklı parşömenlerin ele geçirilmesi. Sadece Uzman rütbesine açıktır.'
                    : 'Infiltrating the forgotten library vaults of the lost Nexari dynasty to retrieve forbidden arcane grimoires.',
                'location' => $isTr ? 'Nexari Kriptaları' : 'Nexari Crypts',
                'reward' => $isTr ? '22,000 XP & Kadim Büyü Kitabı' : '22,000 XP & Ancient Spellbook',
            ],
            [
                'id' => 9,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Quinna\'nın İç Savaşı' : 'Quinna\'s Civil Conflict',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Quinna eyaletindeki klanlar arası iç çatışmada loncamızın stratejik noktaları savunması ve mültecilerin tahliyesi.'
                    : 'Defending strategic guild supply points and evacuating civilians caught in the inter-clan war in Quinna.',
                'location' => $isTr ? 'Quinna Sınır Hattı' : 'Quinna Borderline',
                'reward' => $isTr ? '8,500 XP & Lonca Madalyası' : '8,500 XP & Guild Medal',
            ],
            [
                'id' => 10,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Rolanda Sundrake\'i Bulmak' : 'Locating Rolanda Sundrake',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'on_hold',
                'priority' => 'high',
                'seal_color' => 'blue',
                'description' => $isTr
                    ? 'Kayıp ejderha avcısı Rolanda Sundrake\'in son görüldüğü bataklık arazide aranması. Hava koşulları nedeniyle askıya alınmıştır.'
                    : 'Searching for missing dragon hunter Rolanda Sundrake in the treacherous swamplands where she was last seen.',
                'location' => $isTr ? 'Karasis Bataklığı' : 'Blackmist Mire',
                'reward' => $isTr ? '14,000 XP & Sundrake Nişanı' : '14,000 XP & Sundrake Insignia',
            ],
            [
                'id' => 11,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Şeytanlardan Kurtulmak' : 'Banishing the Fiends',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Yeraltı çatlaklarından sızan habis iblis varlıklarının mühürlenmesi ve kasaba halkının kurtarılması.'
                    : 'Sealing sinister fiends emerging from subterranean abyssal fissures and rescuing the terrorized townsfolk.',
                'location' => $isTr ? 'Kükürt Vadisi' : 'Brimstone Valley',
                'reward' => $isTr ? '20,000 XP & Kutsal Mühür Taşı' : '20,000 XP & Sacred Seal Stone',
            ],
            [
                'id' => 12,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Undine Mağara Araştırması' : 'Undine Cavern Survey',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Sualtı mağaralarında yaşayan su elementallerinin ve Undine kabilesinin sığınaklarının keşfedilmesi.'
                    : 'Exploring coastal sea grottos and charting water elemental sanctuaries inhabited by the Undine tribe.',
                'location' => $isTr ? 'Derin Mavi Koy' : 'Deep Blue Cove',
                'reward' => $isTr ? '6,000 XP & İnci Kolye' : '6,000 XP & Luminous Pearl Necklace',
            ],
            [
                'id' => 13,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Uriel Summertide' : 'Guarding Uriel Summertide',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Uriel Summertide\'ın peşindeki suikastçilerin izinin sürülmesi ve koruma çemberinin kurulması.'
                    : 'Intercepting high-profile assassins pursuing Uriel Summertide and establishing a reinforced sanctuary circle.',
                'location' => $isTr ? 'Güneş Kalesi' : 'Sun Keep',
                'reward' => $isTr ? '13,500 XP & 1,200 GP' : '13,500 XP & 1,200 GP',
            ],
            [
                'id' => 14,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Vane Ailesi Artifact Arayışı' : 'The Vane Family Artifact',
                'eligibility_key' => 'member_senior',
                'eligibility' => $isTr ? 'Üye/Kıdemli' : 'Member / Senior',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Arelion Vane\'in atalarına ait kayıp aile yadigarı eserin (artifact) eski aile mahzeninden kurtarılması.'
                    : 'Recovering the ancestral heirloom artifact of Arelion Vane from the sealed catacombs of the ancestral mausoleum.',
                'location' => $isTr ? 'Vane Aile Mezarlığı' : 'Vane Family Crypt',
                'reward' => $isTr ? '9,000 XP & Aile Onur Plaketi' : '9,000 XP & Family Honor Crest',
            ],
            [
                'id' => 15,
                'category' => 'main',
                'category_label' => $isTr ? 'Ana Görev' : 'Main Quest',
                'title' => $isTr ? 'Warlord\'u Bulmak' : 'Tracking Down the Warlord',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Kuzey topraklarını kana bulayan savaş beyinin gizli karargahının basılması ve ordusunun dağıtılması.'
                    : 'Assaulting the hidden mountain bastion of the northern warlord and routing his pillaging legion.',
                'location' => $isTr ? 'Bozkır Dağları' : 'Steppe Mountains',
                'reward' => $isTr ? '24,000 XP & Savaş Beyi Baltası' : '24,000 XP & Warlord Battleaxe',
            ],

            // ================= MAIN QUESTS: CHARACTER SPECIFIC =================
            [
                'id' => 16,
                'category' => 'main',
                'category_label' => $isTr ? 'Üye Talebi' : 'Member Request',
                'title' => $isTr ? 'Bo\'lcos\'un Tapınakları' : 'Temples of Bo\'lcos',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => 'Desen',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Desen\'in manevi yolculuğu ve Bo\'lcos tapınaklarındaki kadim ritüellerin tamamlanması.'
                    : 'Guiding Desen on a spiritual pilgrimage to fulfill sacred trials at the high shrines of Bo\'lcos.',
                'location' => $isTr ? 'Kutsal Bo\'lcos Dağı' : 'Mount Bo\'lcos Peak',
                'reward' => $isTr ? '8,000 XP & Ruhani Güç Artışı' : '8,000 XP & Spiritual Empowerment',
            ],
            [
                'id' => 17,
                'category' => 'main',
                'category_label' => $isTr ? 'Üye Talebi' : 'Member Request',
                'title' => $isTr ? 'Cennete Yolculuk' : 'Ascent to the Heavens',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => 'Azariah',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Azariah\'ın göksel düzlem (celestial plane) ile bağlarını güçlendirecek kutsal hac görevi.'
                    : 'Azariah\'s pilgrimage to commune with celestial planar hosts and unlock seraphic blessings.',
                'location' => $isTr ? 'Yedi Göğün Zirvesi' : 'Peak of Seven Heavens',
                'reward' => $isTr ? '16,000 XP & Melek Kutsaması' : '16,000 XP & Seraphic Benediction',
            ],
            [
                'id' => 18,
                'category' => 'main',
                'category_label' => $isTr ? 'Üye Talebi' : 'Member Request',
                'title' => $isTr ? 'Finneas\'ı Kurtarma' : 'Rescuing Finneas',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => 'Kerrik',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Kerrik\'in can dostu Finneas\'ın zindandan ve köle tacirlerinin elinden kurtarılması.'
                    : 'Kerrik\'s covert raid to liberate his sworn brother Finneas from high-security slaver dungeon keeps.',
                'location' => $isTr ? 'Demir Kafes Kalesi' : 'Iron Cage Citadel',
                'reward' => $isTr ? '17,500 XP & Kurtarılan Yoldaş' : '17,500 XP & Sworn Companion Bond',
            ],
            [
                'id' => 19,
                'category' => 'main',
                'category_label' => $isTr ? 'Üye Talebi' : 'Member Request',
                'title' => $isTr ? 'Gölge Diyar' : 'The Shadow Realm',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => 'Da-ak T\'aur',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Da-ak T\'aur\'un kökenlerine inerek Gölge Düzlem (Shadow Plane) geçidini kapatma mücadelesi.'
                    : 'Delving into Da-ak T\'aur\'s lineage to banish shadow planar breaches destabilizing reality.',
                'location' => $isTr ? 'Gölge Geçidi' : 'The Umbral Rift',
                'reward' => $isTr ? '19,000 XP & Gölge Pelerini' : '19,000 XP & Mantle of Shadows',
            ],
            [
                'id' => 20,
                'category' => 'main',
                'category_label' => $isTr ? 'Üye Talebi' : 'Member Request',
                'title' => $isTr ? 'Thoma & Taro\'nun Şeytanları' : 'The Demons of Thoma & Taro',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => 'Thoma & Taro',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Thoma ve Taro\'nun geçmişten gelen iblis antlaşmalarını bozmak için ortaklaşa üstlendikleri ölümcül savaş.'
                    : 'Joint crusade undertaken by Thoma and Taro to shatter binding demonic blood contracts once and for all.',
                'location' => $isTr ? 'Alev Çukuru' : 'Infernal Pit',
                'reward' => $isTr ? '21,000 XP & İkiz Ruh Bağı' : '21,000 XP & Twin Soul Binding',
            ],

            // ================= 2. SIDE QUESTS =================
            [
                'id' => 21,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? '"Şanslı Martı" Gemisinin Enkazı' : 'Wreck of the "Lucky Gull"',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Kayalıklara çarparak batan tüccar gemisinin kargosunun yağmacılardan önce kurtarılması. Yeni maceracılar için ideal.'
                    : 'Salvaging valuable spice and silk crates from a grounded merchant caravel before coastal scavengers plunder it.',
                'location' => $isTr ? 'Martı Burnu' : 'Gull Cape',
                'reward' => $isTr ? '2,500 XP & 200 SP' : '2,500 XP & 200 SP',
            ],
            [
                'id' => 22,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Gümüşdilli Hırsız' : 'The Silver-Tongued Thief',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Pazar yerinde soyluları kandırıp mücevherlerini çalan kurnaz hırsızın canlı yakalanması.'
                    : 'Apprehending alive the charming rogue tricking visiting gentry out of their family heirlooms in the town square.',
                'location' => $isTr ? 'Aşağı Pazar Meydanı' : 'Lower Bazaar Square',
                'reward' => $isTr ? '3,200 XP & Hırsız Çantası' : '3,200 XP & Master Thief Satchel',
            ],
            [
                'id' => 23,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Koricunun Kaybı' : 'The Forest Warden\'s Hound',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Orman korucusunun kaybolan sadık av köpeğinin ve harita çantalarının bulunması.'
                    : 'Tracking down the ranger\'s beloved tracking mastiff and recovering lost trail chart satchels in the deep woods.',
                'location' => $isTr ? 'Yeşilçam Ormanı' : 'Greenwood Forest',
                'reward' => $isTr ? '1,800 XP & Orman İksiri' : '1,800 XP & Herbal Ranger Tonic',
            ],
            [
                'id' => 24,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Loomun Oro\'nun İsteği' : 'Loomun Oro\'s Requisition',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Büyücü Loomun Oro\'nun nadir simya deneyleri için ejderha kanı çiçeği toplanması.'
                    : 'Gathering rare scarlet dragonblood orchids blooming on precipitous misty crags for Magister Loomun Oro.',
                'location' => $isTr ? 'Sisli Uçurum' : 'Misty Precipice',
                'reward' => $isTr ? '11,000 XP & Büyülü İksir Seti' : '11,000 XP & Alchemical Elixir Set',
            ],
            [
                'id' => 25,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Öfkeli Güruh' : 'The Agitated Villagers',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Değirmenciye saldırmaya hazırlanan galeyana gelmiş köylüleri kan dökmeden teskin etme görevi.'
                    : 'Diffusing an angry crowd marching on the riverside mill without drawing steel or shedding blood.',
                'location' => $isTr ? 'Nehirboyu Köyü' : 'Riverside Hamlet',
                'reward' => $isTr ? '2,200 XP & Köy Erzağı' : '2,200 XP & Village Provisions',
            ],
            [
                'id' => 26,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Ölü Ağacı Kurutmak' : 'Withering the Blighted Oak',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Lanetli bataklıkta etrafa ölümcül zehir yayan nekrotik ağacın kökünden temizlenmesi.'
                    : 'Excising a rotting necrotic tree that spreads pestilent miasma throughout the marsh waters.',
                'location' => $isTr ? 'Karasu Bataklığı' : 'Blackwater Fen',
                'reward' => $isTr ? '12,500 XP & Nekrotik Koruma Muskası' : '12,500 XP & Necrotic Ward Amulet',
            ],
            [
                'id' => 27,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Paslı Kanişler' : 'Rust Behemoths',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Terk edilmiş demir madenini mesken tutan pas canavarlarının temizlenmesi.'
                    : 'Cleansing a colony of rust-eating horrors devouring old iron veins in the abandoned quarry tunnels.',
                'location' => $isTr ? 'Kızıltepe Madeni' : 'Redhill Quarry',
                'reward' => $isTr ? '2,800 XP & 300 SP' : '2,800 XP & 300 SP',
            ],
            [
                'id' => 28,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Rovesta Krallığı\'nın Talebi' : 'Decree of the Kingdom of Rovesta',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Rovesta Kraliyet Divanı\'nın elçilik heyetine eşlik edecek tecrübeli muhafız ekibi.'
                    : 'Assembling seasoned guild bodyguards to escort high diplomats of the Royal Court of Rovesta through dangerous valleys.',
                'location' => $isTr ? 'Rovesta Sarayı' : 'Rovesta Palace Grounds',
                'reward' => $isTr ? '13,000 XP & Kraliyet Nişanı' : '13,000 XP & Rovestan Royal Insignia',
            ],
            [
                'id' => 29,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Rovestada Arabuluculuk' : 'Guild Mediation in Rovesta',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Rovesta tüccar loncaları arasındaki kanlı husumeti çözmek üzere arabulucu tayin edilmesi.'
                    : 'Serving as an impartial armed arbiter to halt an escalating turf feud between rival merchant houses.',
                'location' => $isTr ? 'Rovesta Lonca Sarayı' : 'Rovesta Merchant Hall',
                'reward' => $isTr ? '10,500 XP & Ticari İmtiyaz' : '10,500 XP & Trade Concessions',
            ],
            [
                'id' => 30,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Wald Ormanında Madenciler' : 'Trapped Miners of Wald Forest',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Wald ormanında mahsur kalan bakır madencilerine yol açılması ve orman kurtlarının kovulması.'
                    : 'Cutting a rescue path through timber wolf packs to relieve trapped copper miners in Wald Forest.',
                'location' => $isTr ? 'Wald Ormanı' : 'Wald Forest',
                'reward' => $isTr ? '3,000 XP & 250 SP' : '3,000 XP & 250 SP',
            ],
            [
                'id' => 31,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Bilinmez Sesler' : 'Strange Echoes Below',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Lonca mahzenlerinin altındaki eski su kanallarından gelen garip uğultuların kaynağının araştırılması.'
                    : 'Investigating odd rhythmic drumming sounds echoing from disused aqueducts beneath the guild cellars.',
                'location' => $isTr ? 'Lonca Yeraltı Tünelleri' : 'Guild Subterrene Tunnels',
                'reward' => $isTr ? '2,400 XP & Antik Para Kesesi' : '2,400 XP & Antique Coin Pouch',
            ],
            [
                'id' => 32,
                'category' => 'side',
                'category_label' => $isTr ? 'Yan Görev' : 'Side Quest',
                'title' => $isTr ? 'Erast\'taki Kule' : 'The Watchtower at Erast',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Erast sınırındaki terkedilmiş gözetleme kulesinin haydutlardan arındırılması ve fenerinin yakılması.'
                    : 'Evicting a band of deserters from the ruined frontier stone tower and relighting the warning beacon.',
                'location' => $isTr ? 'Erast Kulesi' : 'Erast Spire',
                'reward' => $isTr ? '3,500 XP & Gözetleme Dürbünü' : '3,500 XP & Brass Watch Spyglass',
            ],

            // ================= 3. RECURRING QUESTS / PATROLS =================
            [
                'id' => 33,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Derrington Sanat Galerisi' : 'Derrington Relic Hall Watch',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Paha biçilmez antika eserlerin sergilendiği Derrington Galerisi\'nin gece güvenliği ve hırsızlık önleme devriyesi.'
                    : 'Nightly guard watch guarding priceless antiquities against master cat burglars at the Derrington Manor.',
                'location' => $isTr ? 'Derrington Konağı' : 'Derrington Manor',
                'reward' => $isTr ? 'Günlük 6,000 XP & 400 SP' : 'Daily 6,000 XP & 400 SP',
            ],
            [
                'id' => 34,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Greyshield Zindanı' : 'Greyshield Keep Dungeon Guard',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Ağır mahkumların tutulduğu Greyshield zindanında düzenli vardiya nöbeti ve hücre teftişi.'
                    : 'Standing rotation guard shifts and conducting periodic inspection checks along maximum-security cellblocks.',
                'location' => $isTr ? 'Greyshield Kalesi' : 'Greyshield Fortress',
                'reward' => $isTr ? 'Vardiya Başı 2,500 XP & 150 SP' : 'Per Shift 2,500 XP & 150 SP',
            ],
            [
                'id' => 35,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Isenburg Devriyesi' : 'Isenburg Trade Highway Escort',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Isenburg ticaret yolu boyunca tüccar kervanlarına eskortluk etme ve yol güvenliğini sağlama.'
                    : 'Escorting merchant caravans and deterring highway bandits along the bustling Isenburg trade road.',
                'location' => $isTr ? 'Isenburg Yolu' : 'Isenburg Road',
                'reward' => $isTr ? 'Sefer Başı 3,000 XP & 200 SP' : 'Per Run 3,000 XP & 200 SP',
            ],
            [
                'id' => 36,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Soygun Tatbikatı' : 'Stronghold Breach Drill',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Lonca kasalarının güvenliğini test etmek amacıyla düzenlenen savunma ve acil durum tatbikatı.'
                    : 'Periodic drill simulating stronghold security breaches and evaluating defensive ward readiness.',
                'location' => $isTr ? 'Lonca Eğitim Avlusu' : 'Guild Training Yard',
                'reward' => $isTr ? 'Katılım Başı 2,000 XP & Taktik Rozet' : 'Per Drill 2,000 XP & Tactical Pin',
            ],
            [
                'id' => 37,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Lonca Muhafız Nöbeti' : 'Guild Main Gate Vigil',
                'eligibility_key' => 'candidate_member',
                'eligibility' => $isTr ? 'Aday/Üye' : 'Candidate / Member',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'low',
                'seal_color' => 'bronze',
                'description' => $isTr
                    ? 'Valiant Shade ana karargah kapısında gece nöbeti ve gelen ziyaretçilerin kaydının tutulması.'
                    : 'Standing watch over the main fortified gates of Valiant Shade and registering arriving envoys.',
                'location' => $isTr ? 'Valiant Shade Kapısı' : 'Valiant Shade Gate',
                'reward' => $isTr ? 'Nöbet Başı 1,500 XP & 100 SP' : 'Per Watch 1,500 XP & 100 SP',
            ],
            [
                'id' => 38,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Sınır Karakolu Keşfi' : 'Border Outpost Reconnaissance',
                'eligibility_key' => 'all_ranks',
                'eligibility' => $isTr ? 'Hepsi' : 'All Ranks',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Orman sınırındaki karakolların durum raporlarının toplanması ve haber güvercinlerinin beslenmesi.'
                    : 'Collecting intelligence logs from border forest redoubts and maintaining emergency signal pigeon couriers.',
                'location' => $isTr ? 'Doğu Sınırı' : 'Eastern Border Outposts',
                'reward' => $isTr ? '3,500 XP & Sınır Kumanyası' : '3,500 XP & Frontier Rations',
            ],
            [
                'id' => 39,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Büyük Kütüphane Arşiv Taraması' : 'Grand Library Archive Preservation',
                'eligibility_key' => 'senior_expert',
                'eligibility' => $isTr ? 'Kıdemli/Uzman' : 'Senior / Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'medium',
                'seal_color' => 'gold',
                'description' => $isTr
                    ? 'Eski büyü parşömenlerinin tasnifi ve bozulmaya yüz tutmuş elyazmalarının kopyalanması.'
                    : 'Transcribing brittle parchment scrolls and indexing ancient occult tomes in the high scriptorium.',
                'location' => $isTr ? 'Lonca Büyük Kütüphanesi' : 'Guild Grand Library',
                'reward' => $isTr ? '5,000 XP & Parşömen Kopyaları' : '5,000 XP & Transcribed Manuscripts',
            ],
            [
                'id' => 40,
                'category' => 'recurring',
                'category_label' => $isTr ? 'Sürekli Görev' : 'Patrol / Recurring',
                'title' => $isTr ? 'Canavar Avı Devriyesi' : 'Beast Hunt Wilderness Patrol',
                'eligibility_key' => 'expert',
                'eligibility' => $isTr ? 'Uzman' : 'Expert',
                'character' => $isTr ? 'Lonca' : 'Guild',
                'status' => 'active',
                'priority' => 'high',
                'seal_color' => 'red',
                'description' => $isTr
                    ? 'Köylere inen yırtıcı manticore ve griffon tehditlerine karşı kırsal bölgede av devriyesi.'
                    : 'Tracking and slaying vicious manticores and rogue griffons prowling the northern foothills.',
                'location' => $isTr ? 'Kuzey Dağ Etekleri' : 'Northern Foothills',
                'reward' => $isTr ? '15,000 XP & Canavar Postu ve Dişleri' : '15,000 XP & Beast Pelts & Fangs',
            ],
        ];
    }
}
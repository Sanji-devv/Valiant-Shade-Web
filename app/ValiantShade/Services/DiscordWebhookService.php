<?php

namespace App\ValiantShade\Services;

use App\Models\GuildSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhookService
{
    protected function getSettingsPath(): string
    {
        return storage_path('app/guild_settings.json');
    }

    /**
     * Get Discord Webhook URL.
     */
    public function getWebhookUrl(): ?string
    {
        $path = $this->getSettingsPath();
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true) ?: [];
            if (isset($settings['discord_quest_webhook_url']) && !empty($settings['discord_quest_webhook_url'])) {
                return trim($settings['discord_quest_webhook_url']);
            }
        }

        try {
            if (class_exists(GuildSetting::class)) {
                $url = GuildSetting::get('discord_quest_webhook_url');
                if (!empty($url)) {
                    return trim($url);
                }
            }
        } catch (\Throwable $e) {
            // Database not reachable
        }

        return env('DISCORD_QUEST_WEBHOOK_URL', null);
    }

    /**
     * Check if Discord notifications are enabled.
     */
    public function isEnabled(): bool
    {
        $path = $this->getSettingsPath();
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true) ?: [];
            if (isset($settings['discord_quest_webhook_enabled'])) {
                return filter_var($settings['discord_quest_webhook_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        try {
            if (class_exists(GuildSetting::class)) {
                $enabled = GuildSetting::get('discord_quest_webhook_enabled');
                if ($enabled !== null) {
                    return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
                }
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return filter_var(env('DISCORD_QUEST_WEBHOOK_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Save Discord Webhook settings.
     */
    public function saveSettings(string $url, bool $enabled): void
    {
        $url = trim($url);

        // 1. Try DB first
        try {
            if (class_exists(GuildSetting::class)) {
                GuildSetting::set('discord_quest_webhook_url', $url);
                GuildSetting::set('discord_quest_webhook_enabled', $enabled ? '1' : '0');
            }
        } catch (\Throwable $e) {
            // Ignore DB error and save to JSON
        }

        // 2. Save to JSON fallback
        $path = $this->getSettingsPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $settings = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        $settings['discord_quest_webhook_url'] = $url;
        $settings['discord_quest_webhook_enabled'] = $enabled;

        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Send test message to verify webhook connection.
     */
    public function sendTestMessage(?string $customUrl = null): array
    {
        $url = $customUrl ?: $this->getWebhookUrl();
        if (empty($url)) {
            return [
                'success' => false,
                'message' => __('Discord Webhook URL is not configured.'),
            ];
        }

        $isTr = app()->getLocale() === 'tr';

        $payload = [
            'username' => 'Valiant Shade Loncası',
            'avatar_url' => 'https://img.icons8.com/fluency/96/shield.png',
            'embeds' => [
                [
                    'title' => $isTr 
                        ? '🛡️ Lonca Borusu Çalındı: Discord Bağlantısı Başarılı!' 
                        : '🛡️ Guild Horn Resounds: Discord Connection Successful!',
                    'description' => $isTr
                        ? "Valiant Shade Maceracılar Loncası Görev Panosu ile Discord arasındaki kadim iletişim bağı başarıyla mühürlendi.\nArtık panoya asılan yeni fermanlar ve önemli duyurular doğrudan bu kanala iletilecektir."
                        : "The arcane link between the Valiant Shade Guild Quest Board and Discord has been sealed successfully.\nNew notices and decrees pinned to the tavern board will now appear here.",
                    'color' => 0xF59E0B, // Amber Gold
                    'fields' => [
                        [
                            'name' => $isTr ? '📜 Durum' : '📜 Status',
                            'value' => $isTr ? 'Bağlantı Aktif & Hazır' : 'Connected & Active',
                            'inline' => true,
                        ],
                        [
                            'name' => $isTr ? '⏰ Zaman' : '⏰ Timestamp',
                            'value' => now()->format('d.m.Y H:i'),
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'Valiant Shade • Medieval Quest Board',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return $this->postWebhook($url, $payload);
    }

    /**
     * Send a Quest Decree Embed to Discord.
     */
    public function sendQuestDecree(array $quest, string $action = 'pinned'): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => __('Discord notifications are disabled in settings.'),
            ];
        }

        $url = $this->getWebhookUrl();
        if (empty($url)) {
            return [
                'success' => false,
                'message' => __('Discord Webhook URL is not configured.'),
            ];
        }

        $isTr = app()->getLocale() === 'tr';

        // Embed color based on quest category
        $color = match($quest['category'] ?? 'side') {
            'main' => 0x9E2A2B,      // Crimson Red for Main Quests
            'character' => 0x6A0572, // Royal Purple for Member Requests
            'recurring' => 0x1D3557, // Navy Steel for Patrols
            default => 0xD4A373,     // Parchment Gold for Side Quests
        };

        $prefix = match($action) {
            'updated' => ($isTr ? '📝 [FERMAN GÜNCELLENDİ]' : '📝 [DECREE UPDATED]'),
            'status' => ($isTr ? '⚡ [GÖREV DURUMU DEĞİŞTİ]' : '⚡ [QUEST STATUS CHANGED]'),
            default => ($isTr ? '📜 [YENİ LONCA FERMANI]' : '📜 [NEW GUILD DECREE]'),
        };

        $title = $prefix . ' ' . ($quest['title'] ?? __('Untitled Quest'));

        $categoryName = match($quest['category'] ?? 'side') {
            'main' => ($isTr ? '🐉 Ana Görev' : '🐉 Main Quest'),
            'character' => ($isTr ? '👤 Maceracı Talebi' : '👤 Member Request'),
            'recurring' => ($isTr ? '🛡️ Düzenli Devriye / Görev' : '🛡️ Recurring / Patrol'),
            default => ($isTr ? '📜 Yan Görev' : '📜 Side Quest'),
        };

        $statusName = match($quest['status'] ?? 'active') {
            'completed' => ($isTr ? '✅ Tamamlandı' : '✅ Completed'),
            'on_hold' => ($isTr ? '⏸️ Askıya Alındı' : '⏸️ On Hold'),
            'cancelled' => ($isTr ? '❌ İptal Edildi' : '❌ Cancelled'),
            default => ($isTr ? '🟢 Panoda Aktif' : '🟢 Pinned on Board'),
        };

        $fields = [
            [
                'name' => $isTr ? '🏛️ Kategori' : '🏛️ Category',
                'value' => $categoryName,
                'inline' => true,
            ],
            [
                'name' => $isTr ? '🎖️ Uygunluk' : '🎖️ Eligibility',
                'value' => $quest['eligibility'] ?? ($isTr ? 'Hepsi' : 'All Ranks'),
                'inline' => true,
            ],
            [
                'name' => $isTr ? '⚡ Durum' : '⚡ Status',
                'value' => $statusName,
                'inline' => true,
            ],
        ];

        if (!empty($quest['character']) && $quest['character'] !== 'Guild' && $quest['character'] !== 'Lonca') {
            $fields[] = [
                'name' => $isTr ? '👥 Talep Eden / Parti' : '👥 Requester / Party',
                'value' => $quest['character'],
                'inline' => true,
            ];
        }

        if (!empty($quest['location'])) {
            $fields[] = [
                'name' => $isTr ? '📍 Bölge / Konum' : '📍 Location',
                'value' => $quest['location'],
                'inline' => true,
            ];
        }

        if (!empty($quest['reward'])) {
            $fields[] = [
                'name' => $isTr ? '🏆 Lonca Ödülü' : '🏆 Guild Reward',
                'value' => $quest['reward'],
                'inline' => true,
            ];
        }

        $description = !empty($quest['description']) 
            ? "> *" . str_replace("\n", "\n> *", trim($quest['description'])) . "*"
            : ($isTr ? '*Lonca kâtibi bu ferman için henüz ek açıklama düşmedi.*' : '*No details provided.*');

        $payload = [
            'username' => 'Valiant Shade Görev Panosu',
            'avatar_url' => 'https://img.icons8.com/fluency/96/scroll.png',
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $description,
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => $isTr 
                            ? 'Valiant Shade Maceracılar Loncası • Han Panosu' 
                            : 'Valiant Shade Adventurer\'s Guild • Tavern Board',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        return $this->postWebhook($url, $payload);
    }

    /**
     * Perform HTTP POST to Discord webhook endpoint.
     */
    protected function postWebhook(string $url, array $payload): array
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                    'message' => __('Decree announcement was successfully transmitted to Discord!'),
                ];
            }

            Log::warning('Discord Webhook failed: ' . $response->body(), ['status' => $response->status()]);
            return [
                'success' => false,
                'message' => __('Discord returned status code :status: :body', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 100),
                ]),
            ];
        } catch (\Throwable $e) {
            Log::error('Discord Webhook exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('Failed to contact Discord: :msg', ['msg' => $e->getMessage()]),
            ];
        }
    }
}

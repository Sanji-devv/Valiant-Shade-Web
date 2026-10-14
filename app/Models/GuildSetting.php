<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class GuildSetting extends BaseModel
{
    use HasFactory;

    protected $table = 'guild_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get setting value by key with default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     */
    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}

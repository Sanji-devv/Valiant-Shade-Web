<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'password_hash',
        'real_name',
        'name',
        'role',
        'status',
        'active_main_character_id',
        'active_clerk_character_id',
        'current_character',
        'uuid',
    ];

    protected $hidden = [
        'password',
        'password_hash',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            if (empty($user->username) && !empty($user->email)) {
                $user->username = Str::slug(explode('@', $user->email)[0], '_') . '_' . Str::random(4);
            }
            if (empty($user->name) && !empty($user->real_name)) {
                $user->name = $user->real_name;
            } elseif (empty($user->real_name) && !empty($user->name)) {
                $user->real_name = $user->name;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setPasswordAttribute($value): void
    {
        $hashed = Hash::needsRehash($value) ? Hash::make($value) : $value;
        $this->attributes['password'] = $hashed;
        $this->attributes['password_hash'] = $hashed;
    }

    public function setRealNameAttribute($value): void
    {
        $this->attributes['real_name'] = $value;
        if (empty($this->attributes['name'])) {
            $this->attributes['name'] = $value;
        }
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['real_name'])) {
            $this->attributes['real_name'] = $value;
        }
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if (request()->is('demo-vuexy*')) {
            return asset('static/demo-vuexy/assets/img/avatars/1.png');
        }

        if (request()->is('admin*')) {
            return asset('static/admin/assets/img/avatars/1.png');
        }

        return asset('static/valiant-shade/assets/img/avatars/user.png');
    }

    /**
     * Relationship: 1 User ──> N Characters (1:N)
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'user_id');
    }

    public function activeMainCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'active_main_character_id');
    }

    public function activeClerkCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'active_clerk_character_id');
    }

    public function graveyardRecords(): HasMany
    {
        return $this->hasMany(CharacterGraveyard::class, 'user_id');
    }

    public function roleDefinition(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    public function setRoleAttribute($value): void
    {
        $role = strtoupper(Str::slug((string) $value, '_'));
        $this->attributes['role'] = match ($role) {
            'ADMIN' => 'ADMIN',
            'GM', 'GAME_MASTER', 'GAMEMASTER' => 'GM',
            'BAILIFF' => 'BAILIFF',
            'USER', 'PLAYER', '' => 'PLAYER',
            default => $role,
        };
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roleDefinition?->hasPermission($permission) ?? false;
    }

    public function isAdmin(): bool
    {
        return strtoupper($this->role ?? '') === 'ADMIN';
    }

    public function isGM(): bool
    {
        return strtoupper($this->role ?? '') === 'GM';
    }

    public function isPlayer(): bool
    {
        return strtoupper($this->role ?? '') === 'PLAYER';
    }
}

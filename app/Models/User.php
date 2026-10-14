<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPermissions, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'web';

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'password_hash',
        'display_name',
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
            $displayName = $user->display_name ?: ($user->name ?: ($user->username));
            if (!empty($displayName)) {
                $user->display_name = $displayName;
                $user->name = $displayName;
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
            'status' => 'integer',
            'active_main_character_id' => 'integer',
            'active_clerk_character_id' => 'integer',
        ];
    }

    public function userXp(): HasOne
    {
        return $this->hasOne(UserXp::class, 'user_id');
    }

    public function setPasswordAttribute($value): void
    {
        $hashed = Hash::needsRehash($value) ? Hash::make($value) : $value;
        $this->attributes['password'] = $hashed;
        $this->attributes['password_hash'] = $hashed;
    }

    public function setDisplayNameAttribute($value): void
    {
        $this->attributes['display_name'] = $value;
        $this->attributes['name'] = $value;
    }

    public function setRealNameAttribute($value): void
    {
        $this->attributes['display_name'] = $value;
        $this->attributes['name'] = $value;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['display_name'] = $value;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->attributes['display_name']
            ?? $this->attributes['name']
            ?? $this->username
            ?? '';
    }

    public function getRealNameAttribute(): string
    {
        return $this->display_name;
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

    /**
     * Get all roles sorted by authority hierarchy (highest authority first).
     */
    public function getOrderedRolesAttribute()
    {
        $legacySlug = strtoupper((string) ($this->attributes['role'] ?? 'PLAYER'));
        $fallback = collect([(object) [
            'id' => 0,
            'slug' => $legacySlug,
            'name' => $legacySlug === 'ADMIN' ? 'Administrator' : ($legacySlug === 'GM' ? 'Game Master' : 'Player'),
            'description' => null,
            'color' => $legacySlug === 'ADMIN' ? '#ea5455' : ($legacySlug === 'GM' ? '#7367f0' : '#82868b'),
            'icon' => $legacySlug === 'ADMIN' ? 'ti tabler-crown' : ($legacySlug === 'GM' ? 'ti tabler-wand' : 'ti tabler-user'),
            'priority' => $legacySlug === 'ADMIN' ? 100 : ($legacySlug === 'GM' ? 80 : 10),
            'permissions' => [],
        ]]);

        if (! $this->exists) {
            return $fallback;
        }

        try {
            $loaded = $this->relationLoaded('roles')
                ? $this->roles
                : $this->roles()->orderByDesc('priority')->orderBy('name')->get();
            if ($loaded->isNotEmpty()) {
                return $loaded->sortByDesc('priority')->values();
            }

            $fallbackRole = Role::where('slug', $legacySlug)->first();
            if ($fallbackRole) {
                return collect([$fallbackRole]);
            }
        } catch (\Throwable $e) {
            return $fallback;
        }

        return $fallback;
    }

    /**
     * Direct permission names (Spatie), excluding those inherited only via roles.
     *
     * @return list<string>
     */
    public function directPermissionNames(): array
    {
        return $this->permissions->pluck('name')->all();
    }

    /**
     * Highest authority role of the user (e.g. Admin if present, else GM, etc.).
     */
    public function getHighestRoleAttribute(): mixed
    {
        return $this->ordered_roles->first() ?: null;
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
            'VETERAN' => 'VETERAN',
            'MEMBER' => 'MEMBER',
            'USER', 'PLAYER', '' => 'PLAYER',
            default => $role,
        };
    }

    /**
     * Check if user has a specific role slug (e.g. 'ADMIN', 'GM').
     */
    public function hasRole(string|array ...$roleSlugs): bool
    {
        $flattened = [];
        foreach ($roleSlugs as $item) {
            if (is_array($item)) {
                $flattened = array_merge($flattened, $item);
            } else {
                $flattened[] = $item;
            }
        }
        $slugs = array_map('strtoupper', $flattened);

        // Check assigned roles
        if ($this->relationLoaded('roles')) {
            $userSlugs = $this->roles->pluck('slug')->map(fn($s) => strtoupper($s))->all();
        } elseif ($this->exists) {
            try {
                $userSlugs = $this->roles()->pluck('slug')->map(fn($s) => strtoupper($s))->all();
            } catch (\Throwable $e) {
                $userSlugs = [];
            }
        } else {
            $userSlugs = [];
        }

        foreach ($slugs as $slug) {
            if (in_array($slug, $userSlugs, true)) {
                return true;
            }
        }

        // Fallback check against legacy role column
        $legacy = strtoupper((string) ($this->attributes['role'] ?? ''));
        return in_array($legacy, $slugs, true);
    }

    /**
     * Assign a role to the user without duplication.
     */
    public function assignGuildRole(Role|string $role): void
    {
        $roleModel = is_string($role) ? Role::where('slug', strtoupper($role))->first() : $role;
        if ($roleModel) {
            $this->assignRole($roleModel);
            $this->updatePrimaryRole();
        }
    }

    public function removeGuildRole(Role|string $role): void
    {
        $roleModel = is_string($role) ? Role::where('slug', strtoupper($role))->first() : $role;
        if ($roleModel) {
            $this->removeRole($roleModel);
            $this->updatePrimaryRole();
        }
    }

    /**
     * Sync user roles by slug or ID list (guild multi-roles).
     */
    public function syncGuildRoles(array $roleIdentifiers): void
    {
        $roles = collect($roleIdentifiers)->map(function ($identifier) {
            if (is_numeric($identifier)) {
                return Role::find((int) $identifier);
            }

            return Role::where('slug', strtoupper((string) $identifier))->first();
        })->filter();

        $this->syncRoles($roles);
        $this->updatePrimaryRole();
    }

    /**
     * Keep legacy 'role' column synchronized with highest authority role.
     */
    public function updatePrimaryRole(): void
    {
        $highest = $this->roles()->orderByDesc('priority')->first();
        if ($highest) {
            $this->attributes['role'] = $highest->slug;
            $this->saveQuietly();
        }
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permission = str_ends_with($permission, '.manage')
            ? str_replace('.manage', '.all', $permission)
            : $permission;

        try {
            if ($this->can($permission)) {
                return true;
            }

            if (str_contains($permission, '.')) {
                [$module] = explode('.', $permission, 2);

                return $this->can("{$module}.all");
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN') || strtoupper($this->attributes['role'] ?? '') === 'ADMIN';
    }

    public function isGM(): bool
    {
        return $this->hasRole('GM') || strtoupper($this->attributes['role'] ?? '') === 'GM';
    }

    public function isPlayer(): bool
    {
        return $this->hasRole('PLAYER') || strtoupper($this->attributes['role'] ?? '') === 'PLAYER';
    }

    public function isBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole(
            'BAILIFF',
            'BAILIFF_DISCIPLINE',
            'BAILIFF_HERO_RESOURCES',
            'BAILIFF_REPRESENTATIVE',
            'BAILIFF_ARCHIVIST',
            'BAILIFF_OATHKEEPER',
            'BAILIFF_INVENTORY',
            'BAILIFF_QUESTMASTER'
        );
    }

    public function isDisciplineBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_DISCIPLINE', 'BAILIFF') || $this->hasPermission('discipline.manage');
    }

    public function isHeroResourcesBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_HERO_RESOURCES', 'BAILIFF') || $this->hasPermission('hr.manage');
    }

    public function isRepresentativeBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_REPRESENTATIVE', 'BAILIFF') || $this->hasPermission('diplomacy.manage');
    }

    public function isArchivistBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_ARCHIVIST', 'BAILIFF') || $this->hasPermission('archives.manage');
    }

    public function isOathkeeperBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_OATHKEEPER', 'BAILIFF') || $this->hasPermission('oaths.manage');
    }

    public function isInventoryBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_INVENTORY', 'BAILIFF') || $this->hasPermission('inventory.manage');
    }

    public function isQuestmasterBailiff(): bool
    {
        return $this->isAdmin() || $this->hasRole('BAILIFF_QUESTMASTER', 'BAILIFF') || $this->hasPermission('quests.manage');
    }
}

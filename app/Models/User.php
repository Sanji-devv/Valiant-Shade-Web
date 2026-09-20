<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'uuid',
    'role',
    'status',
    'current_character'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
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

    public function characters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CharacterProfile::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }
}

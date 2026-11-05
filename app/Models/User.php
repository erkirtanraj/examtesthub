<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'preferred_locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array|string $roles): bool
    {
        return $this->roles()->whereIn('slug', (array) $roles)->exists();
    }

    public function assignRole(string ...$roles): void
    {
        foreach ($roles as $slug) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => str($slug)->replace('-', ' ')->title()->toString()]
            );

            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();
    }
}

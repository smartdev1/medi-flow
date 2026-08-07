<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => Role::class,
        'is_active' => 'boolean',
    ];

    public function hasRole(Role|string ...$roles): bool
    {
        $values = array_map(fn (Role|string $role) => $role instanceof Role ? $role->value : $role, $roles);

        return in_array($this->role->value, $values, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isDoctor(): bool
    {
        return $this->hasRole(Role::Medecin, Role::Pediatre);
    }

    public function onCallShifts(): HasMany
    {
        return $this->hasMany(OnCallShift::class);
    }

    public function isOnCallToday(): bool
    {
        return $this->onCallShifts()->whereDate('date', today())->exists();
    }

    public function scopeDoctors($query)
    {
        return $query->whereIn('role', [Role::Medecin->value, Role::Pediatre->value]);
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->map(fn (string $part) => Str::upper($part[0]))
            ->take(2)
            ->implode('');
    }
}

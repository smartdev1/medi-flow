<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ActCategory;

class Act extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'category',
        'base_price',
        'insured_price',
        'roles',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'insured_price' => 'integer',
        'roles' => 'array',
        'category' => ActCategory::class,
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getFormattedBasePriceAttribute(): string
    {
        return number_format($this->base_price, 0, ',', ' ').' FCFA';
    }

    public function getFormattedInsuredPriceAttribute(): string
    {
        return $this->insured_price !== null
            ? number_format($this->insured_price, 0, ',', ' ').' FCFA'
            : '—';
    }

    public function rolesAsEnum(): array
    {
        if (! is_array($this->roles)) {
            return [];
        }

        return array_map(
            fn (string $role) => \App\Enums\Role::tryFrom($role),
            $this->roles
        );
    }
}

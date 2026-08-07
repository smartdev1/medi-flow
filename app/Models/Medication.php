<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'unit_price',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(MedicationOrderItem::class);
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 0, ',', ' ').' FCFA';
    }
}

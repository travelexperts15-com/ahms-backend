<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'generic_name', 'category', 'type', 'strength',
        'manufacturer', 'unit', 'stock_quantity', 'reorder_level',
        'purchase_price', 'sale_price', 'expiry_date', 'batch_number', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date'    => 'date',
            'purchase_price' => 'decimal:2',
            'sale_price'     => 'decimal:2',
        ];
    }

    public function dispensations(): HasMany
    {
        return $this->hasMany(MedicineDispensation::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%")
              ->orWhere('batch_number', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_level');
    }
}

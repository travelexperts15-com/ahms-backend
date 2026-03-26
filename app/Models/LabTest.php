<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'category', 'description',
        'sample_type', 'normal_range', 'unit', 'price', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%");
        });
    }
}

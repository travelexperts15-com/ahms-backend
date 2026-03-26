<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_number', 'department_id', 'ward', 'room_number',
        'type', 'status', 'charge_per_day', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'charge_per_day' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function currentAdmission()
    {
        return $this->hasOne(Admission::class)->whereNull('discharge_date')->latest();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('bed_number', 'like', "%{$term}%")
              ->orWhere('ward', 'like', "%{$term}%")
              ->orWhere('room_number', 'like', "%{$term}%");
        });
    }
}

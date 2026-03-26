<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'patient_id', 'doctor_id',
        'opd_visit_id', 'admission_id', 'ordered_date',
        'clinical_notes', 'status', 'ordered_by',
    ];

    protected function casts(): array
    {
        return ['ordered_date' => 'date'];
    }

    public function patient(): BelongsTo  { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo   { return $this->belongsTo(Doctor::class); }
    public function orderedBy(): BelongsTo { return $this->belongsTo(User::class, 'ordered_by'); }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
              ->orWhereHas('patient', fn($q) =>
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_id', 'like', "%{$term}%")
              );
        });
    }
}

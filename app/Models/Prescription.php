<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_number', 'patient_id', 'doctor_id',
        'opd_visit_id', 'admission_id', 'prescribed_date',
        'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['prescribed_date' => 'date'];
    }

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo    { return $this->belongsTo(Doctor::class); }
    public function opdVisit(): BelongsTo  { return $this->belongsTo(OpdVisit::class); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('prescription_number', 'like', "%{$term}%")
              ->orWhereHas('patient', fn($q) =>
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_id', 'like', "%{$term}%")
              );
        });
    }
}

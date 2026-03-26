<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_number', 'patient_id', 'doctor_id', 'department_id', 'bed_id',
        'admission_date', 'admission_time', 'discharge_date', 'discharge_time',
        'admission_type', 'reason_for_admission', 'diagnosis',
        'treatment_summary', 'discharge_notes', 'discharge_condition',
        'status', 'admitted_by', 'discharged_by',
    ];

    protected function casts(): array
    {
        return [
            'admission_date'  => 'date',
            'discharge_date'  => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getLengthOfStayAttribute(): ?int
    {
        if (!$this->discharge_date) return null;
        return $this->admission_date->diffInDays($this->discharge_date);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'admitted');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('admission_number', 'like', "%{$term}%")
              ->orWhere('diagnosis', 'like', "%{$term}%")
              ->orWhereHas('patient', fn($q) =>
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_id', 'like', "%{$term}%")
              );
        });
    }
}

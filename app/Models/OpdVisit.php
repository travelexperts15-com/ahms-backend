<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpdVisit extends Model
{
    use HasFactory;

    protected $table = 'opd_visits';

    protected $fillable = [
        'visit_number', 'patient_id', 'doctor_id', 'department_id', 'appointment_id',
        'visit_date', 'visit_time',
        'chief_complaint', 'history_of_illness', 'examination_findings',
        'diagnosis', 'treatment_plan', 'notes',
        'blood_pressure', 'temperature', 'pulse_rate', 'respiratory_rate',
        'weight', 'height', 'oxygen_saturation',
        'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'  => 'date',
            'temperature' => 'decimal:1',
            'weight'      => 'decimal:2',
            'height'      => 'decimal:2',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('visit_number', 'like', "%{$term}%")
              ->orWhere('diagnosis', 'like', "%{$term}%")
              ->orWhereHas('patient', fn($q) =>
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_id', 'like', "%{$term}%")
              );
        });
    }
}

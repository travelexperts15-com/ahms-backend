<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'blood_group',
        'phone',
        'email',
        'address',
        'marital_status',
        'allergies',
        'chronic_disease',
        'emergency_contact_name',
        'emergency_contact_phone',
        'registration_date',
        'photo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob'               => 'date',
            'registration_date' => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function opdVisits(): HasMany
    {
        return $this->hasMany(OpdVisit::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getAgeAttribute(): int
    {
        return $this->dob->age;
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('images/default-patient.png');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('patient_id', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }
}

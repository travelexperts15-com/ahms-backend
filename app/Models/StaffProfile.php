<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    protected $fillable = [
        'user_id', 'department_id',
        'first_name', 'last_name', 'dob',
        'address', 'emergency_contact_name', 'emergency_contact_phone',
        'joining_date', 'position', 'basic_salary',
        'bank_account', 'national_id', 'marital_status', 'employment_type',
    ];

    protected function casts(): array
    {
        return [
            'dob'          => 'date',
            'joining_date' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

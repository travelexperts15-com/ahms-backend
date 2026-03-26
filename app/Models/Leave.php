<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'leaves';

    protected $fillable = [
        'user_id', 'leave_type', 'start_date', 'end_date',
        'total_days', 'reason', 'rejection_reason',
        'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('user', fn($q) =>
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('employee_id', 'like', "%{$term}%")
        );
    }
}

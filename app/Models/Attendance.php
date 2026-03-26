<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'check_in', 'check_out',
        'work_hours', 'overtime_hours', 'status', 'notes', 'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'work_hours'     => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function markedBy(): BelongsTo { return $this->belongsTo(User::class, 'marked_by'); }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->whereHas('user', fn($q) =>
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('employee_id', 'like', "%{$term}%")
        );
    }
}

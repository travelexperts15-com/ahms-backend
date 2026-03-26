<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'payroll_number', 'month',
        'basic_salary', 'allowances', 'overtime_pay', 'deductions', 'tax', 'net_salary',
        'payment_date', 'payment_method', 'status', 'notes', 'created_by', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date'  => 'date',
            'basic_salary'  => 'decimal:2',
            'allowances'    => 'decimal:2',
            'overtime_pay'  => 'decimal:2',
            'deductions'    => 'decimal:2',
            'tax'           => 'decimal:2',
            'net_salary'    => 'decimal:2',
        ];
    }

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('payroll_number', 'like', "%{$term}%")
              ->orWhere('month', 'like', "%{$term}%")
              ->orWhereHas('user', fn($q) =>
                  $q->where('name', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%")
              );
        });
    }
}

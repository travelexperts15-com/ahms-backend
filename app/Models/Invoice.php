<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'patient_id', 'admission_id', 'opd_visit_id',
        'invoice_date', 'due_date', 'subtotal', 'discount', 'tax',
        'total_amount', 'paid_amount', 'balance', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date'     => 'date',
            'subtotal'     => 'decimal:2',
            'discount'     => 'decimal:2',
            'tax'          => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount'  => 'decimal:2',
            'balance'      => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class); }
    public function opdVisit(): BelongsTo  { return $this->belongsTo(OpdVisit::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
              ->orWhereHas('patient', fn($q) =>
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('patient_id', 'like', "%{$term}%")
              );
        });
    }
}

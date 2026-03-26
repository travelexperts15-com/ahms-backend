<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number', 'invoice_id', 'patient_id', 'amount',
        'payment_method', 'reference_number', 'payment_date', 'notes', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount'       => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo    { return $this->belongsTo(Invoice::class); }
    public function patient(): BelongsTo    { return $this->belongsTo(Patient::class); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}

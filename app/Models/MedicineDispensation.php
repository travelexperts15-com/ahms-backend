<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineDispensation extends Model
{
    protected $fillable = [
        'prescription_id', 'medicine_id', 'medicine_name',
        'quantity', 'unit_price', 'total_price', 'dispensed_by', 'dispensed_at',
    ];

    protected function casts(): array
    {
        return [
            'dispensed_at' => 'datetime',
            'unit_price'   => 'decimal:2',
            'total_price'  => 'decimal:2',
        ];
    }

    public function prescription(): BelongsTo { return $this->belongsTo(Prescription::class); }
    public function medicine(): BelongsTo     { return $this->belongsTo(Medicine::class); }
    public function dispensedBy(): BelongsTo  { return $this->belongsTo(User::class, 'dispensed_by'); }
}

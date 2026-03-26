<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_order_id', 'lab_test_id', 'patient_id',
        'result_value', 'unit', 'normal_range', 'result_flag',
        'remarks', 'performed_by', 'resulted_at',
    ];

    protected function casts(): array
    {
        return ['resulted_at' => 'datetime'];
    }

    public function labOrder(): BelongsTo   { return $this->belongsTo(LabOrder::class); }
    public function labTest(): BelongsTo    { return $this->belongsTo(LabTest::class); }
    public function patient(): BelongsTo    { return $this->belongsTo(Patient::class); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}

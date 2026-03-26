<?php

namespace App\Services;

use App\Models\Admission;

class AdmissionNumberGenerator
{
    public static function next(): string
    {
        $last = Admission::whereNotNull('admission_number')->max('admission_number');

        if (!$last) return 'IPD-0001';

        $number = (int) preg_replace('/\D/', '', $last);
        return 'IPD-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}

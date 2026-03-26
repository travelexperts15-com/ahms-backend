<?php

namespace App\Services;

use App\Models\OpdVisit;

class OpdVisitNumberGenerator
{
    public static function next(): string
    {
        $last = OpdVisit::whereNotNull('visit_number')->max('visit_number');

        if (!$last) return 'OPD-0001';

        $number = (int) preg_replace('/\D/', '', $last);
        return 'OPD-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}

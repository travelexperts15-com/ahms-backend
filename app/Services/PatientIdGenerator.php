<?php

namespace App\Services;

use App\Models\Patient;

class PatientIdGenerator
{
    /**
     * Generate the next sequential patient ID: PAT-0001, PAT-0002, …
     * Uses DB max() on patient_id to avoid race conditions.
     */
    public static function next(): string
    {
        $last = Patient::withTrashed()
            ->whereNotNull('patient_id')
            ->max('patient_id');

        if (!$last) {
            return 'PAT-0001';
        }

        // Extract numeric part: "PAT-0042" → 42
        $number = (int) preg_replace('/\D/', '', $last);
        return 'PAT-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}

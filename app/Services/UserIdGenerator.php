<?php

namespace App\Services;

use App\Models\User;

class UserIdGenerator
{
    /**
     * Generate the next sequential employee ID: EMP-0001, EMP-0002, …
     * Thread-safe via DB max() — no race condition with auto-increment.
     */
    public static function next(): string
    {
        $last = User::withTrashed()
            ->whereNotNull('employee_id')
            ->max('employee_id');

        if (!$last) {
            return 'EMP-0001';
        }

        // Extract numeric part: "EMP-0042" → 42
        $number = (int) preg_replace('/\D/', '', $last);
        return 'EMP-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Services;

use App\Models\Appointment;

class AppointmentNumberGenerator
{
    public static function next(): string
    {
        $last = Appointment::whereNotNull('appointment_number')->max('appointment_number');

        if (!$last) {
            return 'APT-0001';
        }

        $number = (int) preg_replace('/\D/', '', $last);
        return 'APT-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}

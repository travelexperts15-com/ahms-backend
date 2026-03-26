<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extends Spatie's Role model so we can add custom methods/scopes
 * without touching the vendor package.
 */
class Role extends SpatieRole
{
    // Descriptions are stored in the name, but you can add a 'description'
    // column to the roles table later via a migration if needed.

    /**
     * Return a human-readable label from the snake_case name.
     * e.g. "lab_technician" → "Lab Technician"
     */
    public function getLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }

    /**
     * All defined system roles — use this as the source of truth.
     */
    public static function systemRoles(): array
    {
        return [
            'super_admin'    => 'Full system access',
            'admin'          => 'Hospital administration',
            'doctor'         => 'Clinical access — patients, prescriptions, lab orders',
            'nurse'          => 'Ward and patient care',
            'receptionist'   => 'Appointments and patient registration',
            'pharmacist'     => 'Pharmacy and medicine stock',
            'lab_technician' => 'Lab orders and results',
            'accountant'     => 'Billing, invoices and payments',
            'hr_manager'     => 'HR, payroll and attendance',
            'patient'        => 'Patient portal — own records only',
        ];
    }
}

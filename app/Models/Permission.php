<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Extends Spatie's Permission model.
 * Permissions follow a "module.action" naming convention:
 *   e.g. patients.create, patients.edit, billing.view
 */
class Permission extends SpatiePermission
{
    /**
     * Full set of granular permissions grouped by module.
     * Used by the DatabaseSeeder to populate the permissions table.
     */
    public static function systemPermissions(): array
    {
        return [
            // ── Dashboard ──────────────────────────────────────────────
            'dashboard.view',

            // ── Users / Staff ──────────────────────────────────────────
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'users.activate',

            // ── Roles & Permissions ────────────────────────────────────
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'roles.assign',

            // ── Departments ────────────────────────────────────────────
            'departments.view', 'departments.create',
            'departments.edit', 'departments.delete',

            // ── Doctors ────────────────────────────────────────────────
            'doctors.view', 'doctors.create',
            'doctors.edit', 'doctors.delete',

            // ── Patients ──────────────────────────────────────────────
            'patients.view', 'patients.create',
            'patients.edit', 'patients.delete',

            // ── Appointments ──────────────────────────────────────────
            'appointments.view', 'appointments.create',
            'appointments.edit', 'appointments.cancel',

            // ── OPD ───────────────────────────────────────────────────
            'opd.view', 'opd.create', 'opd.edit',

            // ── IPD / Admissions ──────────────────────────────────────
            'ipd.view', 'ipd.admit', 'ipd.discharge', 'ipd.edit',

            // ── Beds / Wards ──────────────────────────────────────────
            'beds.view', 'beds.create', 'beds.edit', 'beds.delete',

            // ── Prescriptions ─────────────────────────────────────────
            'prescriptions.view', 'prescriptions.create',
            'prescriptions.edit', 'prescriptions.print',

            // ── Pharmacy ──────────────────────────────────────────────
            'pharmacy.view', 'pharmacy.create',
            'pharmacy.edit', 'pharmacy.delete', 'pharmacy.stock',

            // ── Laboratory ────────────────────────────────────────────
            'lab.view', 'lab.order', 'lab.results.enter',
            'lab.results.verify',

            // ── Billing ───────────────────────────────────────────────
            'billing.view', 'billing.create',
            'billing.edit', 'billing.delete', 'billing.payment',

            // ── HR & Payroll ──────────────────────────────────────────
            'hr.view', 'hr.payroll', 'hr.attendance',
            'hr.leave.manage',

            // ── Reports ───────────────────────────────────────────────
            'reports.view', 'reports.export',

            // ── Settings ─────────────────────────────────────────────
            'settings.view', 'settings.edit',

            // ── Audit Logs ────────────────────────────────────────────
            'audit.view',
        ];
    }

    /**
     * Which permissions each role gets by default.
     * The seeder uses this map.
     */
    public static function rolePermissionMap(): array
    {
        return [
            'super_admin' => ['*'],  // Gets ALL permissions in the seeder

            'admin' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit', 'users.activate',
                'roles.view', 'roles.assign',
                'departments.view', 'departments.create', 'departments.edit',
                'doctors.view', 'doctors.create', 'doctors.edit',
                'patients.view', 'patients.create', 'patients.edit',
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel',
                'opd.view', 'opd.create', 'opd.edit',
                'ipd.view', 'ipd.admit', 'ipd.discharge', 'ipd.edit',
                'beds.view', 'beds.create', 'beds.edit',
                'prescriptions.view', 'prescriptions.print',
                'pharmacy.view', 'pharmacy.create', 'pharmacy.edit', 'pharmacy.stock',
                'lab.view', 'lab.order',
                'billing.view', 'billing.create', 'billing.edit', 'billing.payment',
                'hr.view', 'hr.payroll', 'hr.attendance', 'hr.leave.manage',
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
                'audit.view',
            ],

            'doctor' => [
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.edit',
                'appointments.view', 'appointments.create', 'appointments.edit',
                'opd.view', 'opd.create', 'opd.edit',
                'ipd.view', 'ipd.edit',
                'prescriptions.view', 'prescriptions.create',
                'prescriptions.edit', 'prescriptions.print',
                'lab.view', 'lab.order',
                'billing.view',
                'reports.view',
            ],

            'nurse' => [
                'dashboard.view',
                'patients.view',
                'appointments.view',
                'opd.view',
                'ipd.view', 'ipd.edit',
                'beds.view',
                'prescriptions.view',
                'lab.view',
            ],

            'receptionist' => [
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.edit',
                'appointments.view', 'appointments.create',
                'appointments.edit', 'appointments.cancel',
                'opd.view', 'opd.create',
                'billing.view', 'billing.create',
                'doctors.view',
                'departments.view',
            ],

            'pharmacist' => [
                'dashboard.view',
                'pharmacy.view', 'pharmacy.create', 'pharmacy.edit',
                'pharmacy.delete', 'pharmacy.stock',
                'prescriptions.view',
                'patients.view',
            ],

            'lab_technician' => [
                'dashboard.view',
                'lab.view', 'lab.order',
                'lab.results.enter', 'lab.results.verify',
                'patients.view',
            ],

            'accountant' => [
                'dashboard.view',
                'billing.view', 'billing.create', 'billing.edit',
                'billing.delete', 'billing.payment',
                'patients.view',
                'reports.view', 'reports.export',
            ],

            'hr_manager' => [
                'dashboard.view',
                'hr.view', 'hr.payroll', 'hr.attendance', 'hr.leave.manage',
                'users.view',
                'reports.view', 'reports.export',
            ],

            'patient' => [
                'appointments.view',
                'prescriptions.view',
                'billing.view',
                'lab.view',
            ],
        ];
    }
}

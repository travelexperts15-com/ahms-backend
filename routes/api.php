<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\IpdController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OpdController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES — No authentication required
// ============================================================

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// ============================================================
// PROTECTED ROUTES — Require valid Sanctum token
// ============================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────────
    Route::post('/logout',          [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me',               [AuthController::class, 'me'])->name('auth.me');
    Route::put('/profile',          [AuthController::class, 'updateProfile'])->name('auth.profile.update');
    Route::put('/change-password',  [AuthController::class, 'changePassword'])->name('auth.password.change');

    // Admin-only: register new user
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('permission:users.create')
        ->name('auth.register');

    // ── Roles & Permissions ───────────────────────────────────────────────────
    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles',        [RoleController::class, 'index'])->name('roles.index');
        Route::get('/permissions',  [RoleController::class, 'permissions'])->name('permissions.index');
    });

    Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->middleware('permission:roles.edit')
        ->name('roles.permissions.sync');

    Route::post('/users/{user}/roles', [RoleController::class, 'assignRole'])
        ->middleware('permission:roles.assign')
        ->name('users.roles.assign');

    // ── Departments ───────────────────────────────────────────────────────────
    Route::middleware('permission:departments.view')->group(function () {
        Route::get('/departments',        [DepartmentController::class, 'index']);
        Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    });
    Route::post('/departments',               [DepartmentController::class, 'store'])
        ->middleware('permission:departments.create');
    Route::put('/departments/{department}',   [DepartmentController::class, 'update'])
        ->middleware('permission:departments.edit');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:departments.delete');

    // ── Staff ─────────────────────────────────────────────────────────────────
    Route::middleware('permission:staff.view')->group(function () {
        Route::get('/staff',        [StaffController::class, 'index']);
        Route::get('/staff/{staff}', [StaffController::class, 'show']);
    });
    Route::post('/staff',               [StaffController::class, 'store'])
        ->middleware('permission:staff.create');
    Route::put('/staff/{staff}',        [StaffController::class, 'update'])
        ->middleware('permission:staff.edit');
    Route::delete('/staff/{staff}',     [StaffController::class, 'destroy'])
        ->middleware('permission:staff.delete');
    Route::patch('/staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])
        ->middleware('permission:staff.edit');

    // ── Doctors ───────────────────────────────────────────────────────────────
    Route::middleware('permission:doctors.view')->group(function () {
        Route::get('/doctors',          [DoctorController::class, 'index']);
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
        Route::get('/doctors/{doctor}/schedules', [DoctorController::class, 'schedules']);
    });
    Route::post('/doctors',             [DoctorController::class, 'store'])
        ->middleware('permission:doctors.create');
    Route::put('/doctors/{doctor}',     [DoctorController::class, 'update'])
        ->middleware('permission:doctors.edit');
    Route::delete('/doctors/{doctor}',  [DoctorController::class, 'destroy'])
        ->middleware('permission:doctors.delete');
    Route::patch('/doctors/{doctor}/toggle-status', [DoctorController::class, 'toggleStatus'])
        ->middleware('permission:doctors.edit');
    Route::post('/doctors/{doctor}/schedules', [DoctorController::class, 'storeSchedule'])
        ->middleware('permission:doctors.edit');
    Route::delete('/doctors/{doctor}/schedules/{schedule}', [DoctorController::class, 'destroySchedule'])
        ->middleware('permission:doctors.edit');

    // ── Patients ──────────────────────────────────────────────────────────────
    Route::middleware('permission:patients.view')->group(function () {
        Route::get('/patients',           [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);
    });
    Route::post('/patients',                        [PatientController::class, 'store'])
        ->middleware('permission:patients.create');
    Route::put('/patients/{patient}',               [PatientController::class, 'update'])
        ->middleware('permission:patients.edit');
    Route::delete('/patients/{patient}',            [PatientController::class, 'destroy'])
        ->middleware('permission:patients.delete');
    Route::patch('/patients/{patient}/toggle-status', [PatientController::class, 'toggleStatus'])
        ->middleware('permission:patients.edit');

    // ── Appointments ──────────────────────────────────────────────────────────
    Route::get('/appointments/today', [AppointmentController::class, 'today'])
        ->middleware('permission:appointments.view');
    Route::middleware('permission:appointments.view')->group(function () {
        Route::get('/appointments',                [AppointmentController::class, 'index']);
        Route::get('/appointments/{appointment}',  [AppointmentController::class, 'show']);
    });
    Route::post('/appointments',                   [AppointmentController::class, 'store'])
        ->middleware('permission:appointments.create');
    Route::put('/appointments/{appointment}',       [AppointmentController::class, 'update'])
        ->middleware('permission:appointments.edit');
    Route::delete('/appointments/{appointment}',    [AppointmentController::class, 'destroy'])
        ->middleware('permission:appointments.delete');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
        ->middleware('permission:appointments.edit');

    // ── OPD ───────────────────────────────────────────────────────────────────
    Route::middleware('permission:opd.view')->group(function () {
        Route::get('/opd',         [OpdController::class, 'index']);
        Route::get('/opd/{visit}', [OpdController::class, 'show']);
    });
    Route::post('/opd',            [OpdController::class, 'store'])->middleware('permission:opd.create');
    Route::put('/opd/{visit}',     [OpdController::class, 'update'])->middleware('permission:opd.edit');
    Route::delete('/opd/{visit}',  [OpdController::class, 'destroy'])->middleware('permission:opd.delete');

    // ── Beds ──────────────────────────────────────────────────────────────────
    Route::get('/beds/available', [BedController::class, 'available'])->middleware('permission:beds.view');
    Route::middleware('permission:beds.view')->group(function () {
        Route::get('/beds',       [BedController::class, 'index']);
        Route::get('/beds/{bed}', [BedController::class, 'show']);
    });
    Route::post('/beds',          [BedController::class, 'store'])->middleware('permission:beds.create');
    Route::put('/beds/{bed}',     [BedController::class, 'update'])->middleware('permission:beds.edit');
    Route::delete('/beds/{bed}',  [BedController::class, 'destroy'])->middleware('permission:beds.delete');

    // ── IPD (Admissions) ──────────────────────────────────────────────────────
    Route::middleware('permission:ipd.view')->group(function () {
        Route::get('/ipd',              [IpdController::class, 'index']);
        Route::get('/ipd/{admission}',  [IpdController::class, 'show']);
    });
    Route::post('/ipd',                              [IpdController::class, 'store'])->middleware('permission:ipd.create');
    Route::put('/ipd/{admission}',                   [IpdController::class, 'update'])->middleware('permission:ipd.edit');
    Route::post('/ipd/{admission}/discharge',         [IpdController::class, 'discharge'])->middleware('permission:ipd.edit');

    // ── Prescriptions ─────────────────────────────────────────────────────────
    Route::middleware('permission:prescriptions.view')->group(function () {
        Route::get('/prescriptions',                  [PrescriptionController::class, 'index']);
        Route::get('/prescriptions/{prescription}',   [PrescriptionController::class, 'show']);
    });
    Route::post('/prescriptions', [PrescriptionController::class, 'store'])
        ->middleware('permission:prescriptions.create');
    Route::patch('/prescriptions/{prescription}/status', [PrescriptionController::class, 'updateStatus'])
        ->middleware('permission:prescriptions.edit');

    // ── Pharmacy ──────────────────────────────────────────────────────────────
    Route::middleware('permission:pharmacy.view')->group(function () {
        Route::get('/pharmacy/medicines',             [PharmacyController::class, 'index']);
        Route::get('/pharmacy/medicines/{medicine}',  [PharmacyController::class, 'show']);
    });
    Route::post('/pharmacy/medicines',               [PharmacyController::class, 'store'])
        ->middleware('permission:pharmacy.create');
    Route::put('/pharmacy/medicines/{medicine}',     [PharmacyController::class, 'update'])
        ->middleware('permission:pharmacy.edit');
    Route::delete('/pharmacy/medicines/{medicine}',  [PharmacyController::class, 'destroy'])
        ->middleware('permission:pharmacy.delete');
    Route::patch('/pharmacy/medicines/{medicine}/adjust-stock', [PharmacyController::class, 'adjustStock'])
        ->middleware('permission:pharmacy.edit');
    Route::post('/pharmacy/dispense/{prescription}', [PharmacyController::class, 'dispense'])
        ->middleware('permission:pharmacy.dispense');

    // ── Laboratory ────────────────────────────────────────────────────────────
    Route::middleware('permission:lab.view')->group(function () {
        Route::get('/lab/tests',          [LabController::class, 'tests']);
        Route::get('/lab/orders',         [LabController::class, 'orders']);
        Route::get('/lab/orders/{order}', [LabController::class, 'showOrder']);
    });
    Route::post('/lab/tests',                            [LabController::class, 'storeTest'])
        ->middleware('permission:lab.create');
    Route::post('/lab/orders',                           [LabController::class, 'storeOrder'])
        ->middleware('permission:lab.create');
    Route::post('/lab/orders/{order}/results',           [LabController::class, 'enterResults'])
        ->middleware('permission:lab.results');
    Route::patch('/lab/orders/{order}/status',           [LabController::class, 'updateOrderStatus'])
        ->middleware('permission:lab.edit');

    // ── Billing ───────────────────────────────────────────────────────────────
    Route::middleware('permission:billing.view')->group(function () {
        Route::get('/billing/invoices',                        [BillingController::class, 'index']);
        Route::get('/billing/invoices/{invoice}',              [BillingController::class, 'show']);
        Route::get('/billing/invoices/{invoice}/payments',     [BillingController::class, 'payments']);
    });
    Route::post('/billing/invoices',                           [BillingController::class, 'store'])
        ->middleware('permission:billing.create');
    Route::patch('/billing/invoices/{invoice}/cancel',         [BillingController::class, 'cancel'])
        ->middleware('permission:billing.edit');
    Route::post('/billing/invoices/{invoice}/payments',        [BillingController::class, 'storePayment'])
        ->middleware('permission:billing.create');

    // ── Payroll ───────────────────────────────────────────────────────────────
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('/payroll',          [PayrollController::class, 'index']);
        Route::get('/payroll/{payroll}',[PayrollController::class, 'show']);
    });
    Route::post('/payroll',                        [PayrollController::class, 'store'])->middleware('permission:payroll.create');
    Route::post('/payroll/bulk-generate',          [PayrollController::class, 'bulkGenerate'])->middleware('permission:payroll.create');
    Route::patch('/payroll/{payroll}/approve',     [PayrollController::class, 'approve'])->middleware('permission:payroll.approve');
    Route::patch('/payroll/{payroll}/mark-paid',   [PayrollController::class, 'markPaid'])->middleware('permission:payroll.approve');

    // ── Attendance ────────────────────────────────────────────────────────────
    Route::get('/attendance/summary', [AttendanceController::class, 'summary'])->middleware('permission:attendance.view');
    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('/attendance',             [AttendanceController::class, 'index']);
        Route::get('/attendance/{attendance}',[AttendanceController::class, 'show']);
    });
    Route::post('/attendance',                [AttendanceController::class, 'store'])->middleware('permission:attendance.create');
    Route::put('/attendance/{attendance}',    [AttendanceController::class, 'update'])->middleware('permission:attendance.edit');

    // ── Leave ─────────────────────────────────────────────────────────────────
    Route::middleware('permission:leave.view')->group(function () {
        Route::get('/leaves',         [LeaveController::class, 'index']);
        Route::get('/leaves/{leave}', [LeaveController::class, 'show']);
    });
    Route::post('/leaves',                   [LeaveController::class, 'store'])->middleware('permission:leave.apply');
    Route::patch('/leaves/{leave}/approve',  [LeaveController::class, 'approve'])->middleware('permission:leave.approve');
    Route::patch('/leaves/{leave}/reject',   [LeaveController::class, 'reject'])->middleware('permission:leave.approve');
    Route::patch('/leaves/{leave}/cancel',   [LeaveController::class, 'cancel'])->middleware('permission:leave.apply');

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::get('/dashboard',                 [DashboardController::class, 'index']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/patients',      [ReportController::class, 'patients']);
        Route::get('/reports/appointments',  [ReportController::class, 'appointments']);
        Route::get('/reports/admissions',    [ReportController::class, 'admissions']);
        Route::get('/reports/revenue',       [ReportController::class, 'revenue']);
        Route::get('/reports/lab',           [ReportController::class, 'lab']);
        Route::get('/reports/attendance',    [ReportController::class, 'attendance']);
    });

    // ── Settings ──────────────────────────────────────────────────────────────
    Route::get('/settings',        [SettingsController::class, 'index']);
    Route::get('/settings/{key}',  [SettingsController::class, 'show']);
    Route::put('/settings',        [SettingsController::class, 'update'])->middleware('permission:settings.edit');

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/mark-all-read',              [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read',        [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}',            [NotificationController::class, 'destroy']);

    // ── Audit Logs ────────────────────────────────────────────────────────────
    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit-logs',        [AuditController::class, 'index']);
        Route::get('/audit-logs/events', [AuditController::class, 'events']);
    });
});

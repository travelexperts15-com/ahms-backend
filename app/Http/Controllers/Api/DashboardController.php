<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    // GET /api/dashboard
    public function index(Request $request): JsonResponse
    {
        $today = today();

        return $this->success([

            // ── Patients ──────────────────────────────────────────────────────
            'patients' => [
                'total'            => Patient::count(),
                'active'           => Patient::where('status', 'active')->count(),
                'registered_today' => Patient::whereDate('registration_date', $today)->count(),
            ],

            // ── Appointments ──────────────────────────────────────────────────
            'appointments' => [
                'today_total'    => Appointment::whereDate('appointment_date', $today)->count(),
                'today_pending'  => Appointment::whereDate('appointment_date', $today)->where('status', 'scheduled')->count(),
                'today_completed'=> Appointment::whereDate('appointment_date', $today)->where('status', 'completed')->count(),
            ],

            // ── IPD ───────────────────────────────────────────────────────────
            'ipd' => [
                'current_admissions' => Admission::where('status', 'admitted')->count(),
                'admitted_today'     => Admission::whereDate('admission_date', $today)->count(),
                'discharged_today'   => Admission::whereDate('discharge_date', $today)->count(),
            ],

            // ── Beds ──────────────────────────────────────────────────────────
            'beds' => [
                'total'       => Bed::count(),
                'available'   => Bed::where('status', 'available')->count(),
                'occupied'    => Bed::where('status', 'occupied')->count(),
                'maintenance' => Bed::where('status', 'maintenance')->count(),
            ],

            // ── Lab ───────────────────────────────────────────────────────────
            'lab' => [
                'pending_orders'   => LabOrder::whereIn('status', ['pending', 'sample_collected', 'processing'])->count(),
                'completed_today'  => LabOrder::where('status', 'completed')->whereDate('updated_at', $today)->count(),
            ],

            // ── Pharmacy ──────────────────────────────────────────────────────
            'pharmacy' => [
                'low_stock_medicines' => Medicine::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
                'out_of_stock'        => Medicine::where('status', 'out_of_stock')->count(),
                'pending_dispense'    => Prescription::where('status', 'pending')->count(),
            ],

            // ── Billing ───────────────────────────────────────────────────────
            'billing' => [
                'revenue_today'    => Invoice::whereDate('invoice_date', $today)->where('status', '!=', 'cancelled')->sum('total_amount'),
                'revenue_month'    => Invoice::whereMonth('invoice_date', $today->month)->whereYear('invoice_date', $today->year)->where('status', '!=', 'cancelled')->sum('total_amount'),
                'pending_invoices' => Invoice::whereIn('status', ['pending', 'partial'])->count(),
                'unpaid_balance'   => Invoice::whereIn('status', ['pending', 'partial'])->sum('balance'),
            ],

        ], 'Dashboard data retrieved.');
    }

    // GET /api/dashboard/recent-activity
    public function recentActivity(): JsonResponse
    {
        return $this->success([
            'recent_admissions' => Admission::with('patient')
                ->where('status', 'admitted')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($a) => [
                    'number'     => $a->admission_number,
                    'patient'    => $a->patient->first_name . ' ' . $a->patient->last_name,
                    'patient_id' => $a->patient->patient_id,
                    'date'       => $a->admission_date?->toDateString(),
                ]),

            'recent_appointments' => Appointment::with(['patient', 'doctor'])
                ->whereDate('appointment_date', today())
                ->orderBy('appointment_time')
                ->limit(5)
                ->get()
                ->map(fn($a) => [
                    'number'  => $a->appointment_number,
                    'patient' => $a->patient->first_name . ' ' . $a->patient->last_name,
                    'doctor'  => 'Dr. ' . $a->doctor->first_name . ' ' . $a->doctor->last_name,
                    'time'    => $a->appointment_time,
                    'status'  => $a->status,
                ]),

            'recent_invoices' => Invoice::with('patient')
                ->whereIn('status', ['pending', 'partial'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($i) => [
                    'number'  => $i->invoice_number,
                    'patient' => $i->patient->first_name . ' ' . $i->patient->last_name,
                    'amount'  => $i->total_amount,
                    'balance' => $i->balance,
                    'status'  => $i->status,
                ]),
        ], 'Recent activity retrieved.');
    }
}

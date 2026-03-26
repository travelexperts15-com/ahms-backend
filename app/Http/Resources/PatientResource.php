<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Identity ──────────────────────────────────────────────────────
            'id'             => $this->id,
            'patient_id'     => $this->patient_id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'gender'         => $this->gender,
            'dob'            => $this->dob?->toDateString(),
            'age'            => $this->age,              // computed accessor
            'blood_group'    => $this->blood_group,

            // ── Contact ───────────────────────────────────────────────────────
            'phone'          => $this->phone,
            'email'          => $this->email,
            'address'        => $this->address,
            'marital_status' => $this->marital_status,
            'photo_url'      => $this->photo_url,        // accessor: full URL

            // ── Medical info ──────────────────────────────────────────────────
            'allergies'       => $this->allergies,
            'chronic_disease' => $this->chronic_disease,

            // ── Emergency contact ─────────────────────────────────────────────
            'emergency_contact_name'  => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,

            // ── Registration ──────────────────────────────────────────────────
            'registration_date' => $this->registration_date?->toDateString(),
            'status'            => $this->status,
            'created_at'        => $this->created_at?->toDateTimeString(),

            // ── Relationships (only when eager-loaded) ────────────────────────
            'appointments'  => $this->whenLoaded('appointments'),
            'opd_visits'    => $this->whenLoaded('opdVisits'),
            'admissions'    => $this->whenLoaded('admissions'),
            'prescriptions' => $this->whenLoaded('prescriptions'),
            'lab_results'   => $this->whenLoaded('labResults'),
            'invoices'      => $this->whenLoaded('invoices'),
        ];
    }
}

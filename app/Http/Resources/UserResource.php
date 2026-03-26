<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // getAllPermissions() queries DB if roles aren't cached.
        // We always eager-load roles before passing to this resource,
        // so permissions resolve from the already-loaded role relations.
        $roles       = $this->roles->pluck('name');
        $permissions = $this->getAllPermissions()->pluck('name')->unique()->values();

        return [
            // ── Core identity ─────────────────────────────────────────────
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'employee_id' => $this->employee_id,
            'phone'       => $this->phone,
            'gender'      => $this->gender,
            'photo_url'   => $this->photo_url,   // Accessor: full storage URL
            'is_active'   => $this->is_active,

            // ── Roles & permissions ───────────────────────────────────────
            // Always included — no conditional loading needed.
            // Roles are always eager-loaded before this resource is called.
            'roles'        => $roles,
            'permissions'  => $permissions,
            'primary_role' => $roles->first(),  // e.g. "doctor"

            // ── Related profiles (only when explicitly eager-loaded) ───────
            'staff_profile' => $this->whenLoaded('staffProfile', fn() =>
                $this->staffProfile
                    ? new StaffProfileResource($this->staffProfile)
                    : null
            ),
            'doctor_profile' => $this->whenLoaded('doctor', fn() =>
                $this->doctor
                    ? new DoctorResource($this->doctor)
                    : null
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

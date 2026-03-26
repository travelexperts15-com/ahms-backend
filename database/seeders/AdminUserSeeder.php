<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  Creating admin user...');

        // ── Super Admin ───────────────────────────────────────────────────────
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@ahms.com'],
            [
                'name'        => 'Super Admin',
                'password'    => Hash::make('Admin@12345'),
                'employee_id' => 'EMP-0001',
                'phone'       => '+252614900313',
                'gender'      => 'male',
                'is_active'   => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);
        $this->command->line('    ✓ super_admin: superadmin@ahms.com / Admin@12345');

        // ── Hospital Admin ────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@ahms.com'],
            [
                'name'        => 'Hospital Admin',
                'password'    => Hash::make('Admin@12345'),
                'employee_id' => 'EMP-0002',
                'phone'       => '+252614900314',
                'gender'      => 'male',
                'is_active'   => true,
            ]
        );
        $admin->syncRoles(['admin']);
        $this->command->line('    ✓ admin: admin@ahms.com / Admin@12345');

        // ── Sample Doctor ─────────────────────────────────────────────────────
        $doctor = User::firstOrCreate(
            ['email' => 'doctor@ahms.com'],
            [
                'name'        => 'Dr. Ahmed Hassan',
                'password'    => Hash::make('Doctor@12345'),
                'employee_id' => 'EMP-0003',
                'phone'       => '+252614900315',
                'gender'      => 'male',
                'is_active'   => true,
            ]
        );
        $doctor->syncRoles(['doctor']);
        $this->command->line('    ✓ doctor: doctor@ahms.com / Doctor@12345');

        // ── Sample Receptionist ───────────────────────────────────────────────
        $receptionist = User::firstOrCreate(
            ['email' => 'receptionist@ahms.com'],
            [
                'name'        => 'Fatima Mohamed',
                'password'    => Hash::make('Staff@12345'),
                'employee_id' => 'EMP-0004',
                'phone'       => '+252614900316',
                'gender'      => 'female',
                'is_active'   => true,
            ]
        );
        $receptionist->syncRoles(['receptionist']);
        $this->command->line('    ✓ receptionist: receptionist@ahms.com / Staff@12345');
    }
}

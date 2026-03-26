<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  Creating departments...');

        $departments = [
            ['name' => 'Emergency',          'code' => 'EMRG', 'location' => 'Ground Floor, Wing A'],
            ['name' => 'Outpatient (OPD)',    'code' => 'OPD',  'location' => 'Ground Floor, Wing B'],
            ['name' => 'Inpatient (IPD)',     'code' => 'IPD',  'location' => 'First Floor, Wing A'],
            ['name' => 'Intensive Care',      'code' => 'ICU',  'location' => 'First Floor, Wing B'],
            ['name' => 'Surgery',             'code' => 'SURG', 'location' => 'Second Floor, Wing A'],
            ['name' => 'Pediatrics',          'code' => 'PED',  'location' => 'Second Floor, Wing B'],
            ['name' => 'Obstetrics & Gynecology', 'code' => 'OBGYN', 'location' => 'Third Floor, Wing A'],
            ['name' => 'Radiology',           'code' => 'RAD',  'location' => 'Ground Floor, Wing C'],
            ['name' => 'Laboratory',          'code' => 'LAB',  'location' => 'Ground Floor, Wing D'],
            ['name' => 'Pharmacy',            'code' => 'PHAR', 'location' => 'Ground Floor, Wing E'],
            ['name' => 'Cardiology',          'code' => 'CARD', 'location' => 'Third Floor, Wing B'],
            ['name' => 'Orthopedics',         'code' => 'ORTH', 'location' => 'Fourth Floor, Wing A'],
            ['name' => 'Human Resources',     'code' => 'HR',   'location' => 'Admin Block'],
            ['name' => 'Finance & Billing',   'code' => 'FIN',  'location' => 'Admin Block'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['status' => 'active'])
            );
        }

        $this->command->line('    ✓ ' . count($departments) . ' departments seeded.');
    }
}

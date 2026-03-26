<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed order matters — roles must exist before users are assigned to them.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════');
        $this->command->info('  AHMS — Seeding database');
        $this->command->info('═══════════════════════════════════════════════');

        $this->call([
            RolesAndPermissionsSeeder::class, // 1st — must exist before users
            AdminUserSeeder::class,           // 2nd — assigns roles to users
            DepartmentSeeder::class,          // 3rd — must exist before staff/doctors
            SettingsSeeder::class,            // 4th — system configuration
        ]);

        $this->command->info('');
        $this->command->info('  All done. Database seeded successfully.');
        $this->command->info('═══════════════════════════════════════════════');
    }
}

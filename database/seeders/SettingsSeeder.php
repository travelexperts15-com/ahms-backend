<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  Creating system settings...');

        $settings = [
            // General
            ['key' => 'hospital_name',    'value' => 'AFGOI Hospital',    'group' => 'general',  'type' => 'string',  'label' => 'Hospital Name'],
            ['key' => 'hospital_address', 'value' => 'Afgooye, Somalia',  'group' => 'general',  'type' => 'string',  'label' => 'Hospital Address'],
            ['key' => 'hospital_phone',   'value' => '+252614900313',      'group' => 'general',  'type' => 'string',  'label' => 'Hospital Phone'],
            ['key' => 'hospital_email',   'value' => 'info@ahms.com',      'group' => 'general',  'type' => 'string',  'label' => 'Hospital Email'],
            ['key' => 'currency',         'value' => 'USD',                'group' => 'general',  'type' => 'string',  'label' => 'Currency'],
            ['key' => 'timezone',         'value' => 'Africa/Mogadishu',   'group' => 'general',  'type' => 'string',  'label' => 'Timezone'],

            // Billing
            ['key' => 'invoice_prefix',   'value' => 'INV',               'group' => 'billing',  'type' => 'string',  'label' => 'Invoice Prefix'],
            ['key' => 'tax_rate',         'value' => '0',                  'group' => 'billing',  'type' => 'integer', 'label' => 'Tax Rate (%)'],
            ['key' => 'payment_terms',    'value' => '30',                 'group' => 'billing',  'type' => 'integer', 'label' => 'Payment Terms (days)'],

            // HR
            ['key' => 'working_hours_per_day', 'value' => '8',            'group' => 'hr',       'type' => 'integer', 'label' => 'Working Hours Per Day'],
            ['key' => 'working_days_per_week', 'value' => '6',            'group' => 'hr',       'type' => 'integer', 'label' => 'Working Days Per Week'],
            ['key' => 'annual_leave_days',     'value' => '21',           'group' => 'hr',       'type' => 'integer', 'label' => 'Annual Leave Days'],

            // Appointments
            ['key' => 'appointment_slot_minutes', 'value' => '30',        'group' => 'appointments', 'type' => 'integer', 'label' => 'Appointment Slot (minutes)'],
            ['key' => 'max_daily_appointments',   'value' => '20',        'group' => 'appointments', 'type' => 'integer', 'label' => 'Max Daily Appointments Per Doctor'],

            // Pharmacy
            ['key' => 'low_stock_alert',  'value' => '1',                 'group' => 'pharmacy', 'type' => 'boolean', 'label' => 'Low Stock Alerts'],
            ['key' => 'expiry_alert_days','value' => '30',                'group' => 'pharmacy', 'type' => 'integer', 'label' => 'Expiry Alert Days Before'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $this->command->line('    ✓ ' . count($settings) . ' settings seeded.');
    }
}

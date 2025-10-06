<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class MaldiveBanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banks = [
            [
                'name' => 'Bank of Maldives',
                'code' => 'BML',
                'full_name' => 'Bank of Maldives PLC',
                'country' => 'MV',
                'logo_url' => 'https://bml.com.mv/assets/images/logo.png',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Maldivian Heritage Bank',
                'code' => 'MHB',
                'full_name' => 'Maldivian Heritage Bank Private Limited',
                'country' => 'MV',
                'logo_url' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Maldives Islamic Bank',
                'code' => 'MIB',
                'full_name' => 'Maldives Islamic Bank PLC',
                'country' => 'MV',
                'logo_url' => 'https://mib.com.mv/assets/images/logo.png',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'State Bank of India',
                'code' => 'SBI',
                'full_name' => 'State Bank of India - Maldives Branch',
                'country' => 'MV',
                'logo_url' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Habib Bank Limited',
                'code' => 'HBL',
                'full_name' => 'Habib Bank Limited - Maldives Branch',
                'country' => 'MV',
                'logo_url' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'HDFC Bank',
                'code' => 'HDFC',
                'full_name' => 'HDFC Bank Limited - Maldives Branch',
                'country' => 'MV',
                'logo_url' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Mauritius Commercial Bank',
                'code' => 'MCB',
                'full_name' => 'Mauritius Commercial Bank (Maldives) Private Limited',
                'country' => 'MV',
                'logo_url' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($banks as $bank) {
            $exists = DB::table('system_banks')
                ->where('code', $bank['code'])
                ->where('country', $bank['country'])
                ->exists();
            
            if (!$exists) {
                DB::table('system_banks')->insert($bank);
            }
        }
    }
}
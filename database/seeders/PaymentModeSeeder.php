<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('payment_mode')->insert(
            [
                'name' => 'Yearly',
            ]
        );
        
        DB::table('payment_mode')->insert(
            [
                'name' => 'Semester',
            ]
        );
        
        DB::table('payment_mode')->insert(
            [
                'name' => 'Monthly',
            ]
        );
    }
}

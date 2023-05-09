<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('payment_status')->insert(
            [
                'name' => 'paid',
            ]
        );
        DB::table('payment_status')->insert(
            [
                'name' => 'unpaid',
            ]
        );
        DB::table('payment_status')->insert(
            [
                'name' => 'delay',
            ]
        );
    }
}

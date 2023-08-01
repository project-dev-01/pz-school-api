<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckInOutTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('check_in_out_time')->insert(
            [
                'check_in' => null,
                'check_out' => null,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('leave_types')->insert(
            [
                'name' => 'Casual Leave',
                'leave_days' => '15',
                'gender' => 'All',
                'short_name' => 'CL',
            ]
        );
        DB::table('leave_types')->insert(
            [
                'name' => 'Sick Leave',
                'leave_days' => '10',
                'gender' => 'All',
                'short_name' => 'SL',
            ]
        );
        DB::table('leave_types')->insert(
            [
                'name' => 'Maternity Leave',
                'leave_days' => '30',
                'gender' => 'Female',
                'short_name' => 'ML',
            ]
        );
        
    }
}

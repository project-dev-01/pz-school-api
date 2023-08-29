<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branches;
use App\Helpers\DatabaseConnection;

class WorkWeekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $params = Branches::select('id','db_name','db_username','db_password','db_port','db_host')->where('id',1)->first();
        // $con = DatabaseConnection::setConnection($params);
        DB::table('work_weeks')->insert(
            [
                'day' => 'Sunday',
                'day_value' => '0',
                'status' => '0'
            ],
            [
                'day' => 'Monday',
                'day_value' => '1',
                'status' => '0'
            ],
            [
                'day' => 'Tuesday',
                'day_value' => '2',
                'status' => '0'
            ],
            [
                'day' => 'Wednesday',
                'day_value' => '3',
                'status' => '0'
            ],
            [
                'day' => 'Thursday',
                'day_value' => '4',
                'status' => '0'
            ],
            [
                'day' => 'Friday',
                'day_value' => '5',
                'status' => '0'
            ],
            [
                'day' => 'Saturday',
                'day_value' => '6',
                'status' => '0'
            ]
        );
    }
}

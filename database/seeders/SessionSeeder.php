<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branches;
use App\Helpers\DatabaseConnection;

class SessionSeeder extends Seeder
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
        DB::table('session')->insert(
            [
                'name' => 'Morning',
                'time_from' => '08:00:00',
                'time_to' => '13:00:00'
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('relations')->insert(
            [
                'name' => 'Uncle',
            ]
        );
        DB::table('relations')->insert(
            [
                'name' => 'Father',
            ]
        );
        DB::table('relations')->insert(
            [
                'name' => 'Mother',
            ]
        );
        DB::table('relations')->insert(
            [
                'name' => 'Aunty',
            ]
        );
        DB::table('relations')->insert(
            [
                'name' => 'Brother',
            ]
        );
        DB::table('relations')->insert(
            [
                'name' => 'Sister',
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {


        DB::table('roles')->insert(
            [
                'role_name' => 'Super Admin',
                'role_slug' => 'super admin',
                'status' => '1'
            ]
        );
        DB::table('roles')->insert(
            [
                'role_name' => 'Admin',
                'role_slug' => 'admin',
                'status' => '0'
            ]
        );
        DB::table('roles')->insert(
            [
                'role_name' => 'Staff',
                'role_slug' => 'staff',
                'status' => '0'
            ]
        );
        DB::table('roles')->insert(
            [
                'role_name' => 'Teacher',
                'role_slug' => 'teacher',
                'status' => '0'
            ]
        );
        DB::table('roles')->insert(
            [
                'role_name' => 'Parent',
                'role_slug' => 'parent',
                'status' => '2'
            ]
        );
        DB::table('roles')->insert(
            [
                'role_name' => 'Student',
                'role_slug' => 'student',
                'status' => '2'
            ]
        );
        //
    }
}

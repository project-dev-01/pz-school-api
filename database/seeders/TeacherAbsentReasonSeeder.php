<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherAbsentReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Covid-19',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Bersalin',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Haji',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Kecerdasan',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Kecemasan Am',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Kuarantin',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Kursus Sambilan (PJJ)',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Kursus/Belajar',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Menjaga (Cuti Tanpa Gaji)',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Rehat',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Rehat Khas',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Sakit',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Sakit Lanjutan',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Separuh Gaji',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Tanpa Gaji',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Tanpa Gaji Ikut Pasangan',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Tanpa Rekod',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Tibi, Kusta dan Barah',
            ]
        );
        DB::table('teacher_absent_reasons')->insert(
            [
                'name' => 'Cuti Umrah',
            ]
        );
    }
}

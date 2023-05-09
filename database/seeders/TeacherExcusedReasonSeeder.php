<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherExcusedReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Bekerja dari rumah',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Bengkel',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Bertugas Dalam Kokurikulum/KoAkademik/Sukan',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Kursus',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Mesyurarat',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Pengawas Peperiksaan',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Proses Pemulihan di JPN',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Proses Pemilihan di PPD',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Tahan/Gantung Kerja (Tatatertib)',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Taklimat',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Tanpa Kenyataan',
            ]
        );
        DB::table('teacher_excused_reasons')->insert(
            [
                'name' => 'Tugas Rasmi',
            ]
        );
    }
}

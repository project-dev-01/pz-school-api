<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaperTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('paper_type')->insert(
            [
                'name' => 'Objective',
            ]
        );
        DB::table('paper_type')->insert(
            [
                'name' => 'Experiment',
            ]
        );
        DB::table('paper_type')->insert(
            [
                'name' => 'Subjective',
            ]
        );
        DB::table('paper_type')->insert(
            [
                'name' => 'Presentation',
            ]
        );
        DB::table('paper_type')->insert(
            [
                'name' => 'Objective + Subjective',
            ]
        );
        DB::table('paper_type')->insert(
            [
                'name' => 'Oral',
            ]
        );
    }
}

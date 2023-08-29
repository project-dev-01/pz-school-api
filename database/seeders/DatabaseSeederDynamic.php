<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeederDynamic extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            GlobalSettingSeeder::class,
            CheckInOutTimeSeeder::class,
            PaymentModeSeeder::class,
            PaymentStatusSeeder::class,
            RelationSeeder::class,
            SessionSeeder::class,
            TeacherAbsentReasonSeeder::class,
            TeacherExcusedReasonSeeder::class,
            PaperTypeSeeder::class,
            WorkWeekSeeder::class
            
        ]);
        // User::factory(10)->create();
    }
}

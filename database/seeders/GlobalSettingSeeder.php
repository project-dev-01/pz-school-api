<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('global_settings')->insert(
            [
                'year_id' => '1',
                'footer_text' => '© 2020 Paxsuzen School Management - Developed by Aibots',
                'facebook_url' => 'https://www.facebook.com/username',
                'twitter_url' => 'https://www.twitter.com/username',
                'linkedin_url' => 'https://www.linkedin.com/usernames',
                'youtube_url' => 'https://www.youtube.com/username',
            ]
        );
    }
}

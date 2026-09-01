<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LanguagesSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::Now();

        DB::table('languages')->insert([
            [
                'code' => 'en',
                'php_locale_code' => 'en_US',
                'language' => 'English',
                'order' => 1,
                'rtl' => false,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pt',
                'php_locale_code' => 'pt_PT',
                'language' => 'Portuguese',
                'order' => 2,
                'rtl' => false,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

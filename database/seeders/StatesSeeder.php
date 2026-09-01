<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatesSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = __DIR__.'/data/states';
        $files = glob($filePath.'/*.json');

        if (empty($files)) {
            $this->command?->warn('No state JSON files found in database/seeders/data/states.');

            return;
        }

        $now = Carbon::now();
        $seededCountries = 0;
        $skippedCountries = 0;
        $seededStates = 0;

        foreach ($files as $file) {
            $countryCode = basename($file, '.json');
            $country = DB::table('countries')->where('iso_code', $countryCode)->first();

            if (! $country) {
                $skippedCountries++;
                continue;
            }

            $json = json_decode(file_get_contents($file), true);

            if (! is_array($json)) {
                continue;
            }

            usort($json, function ($a, $b) {
                return strcmp($a['name'] ?? '', $b['name'] ?? '');
            });

            foreach ($json as $state) {
                if (empty($state['iso_code']) || empty($state['name'])) {
                    continue;
                }

                DB::table('states')->updateOrInsert(
                    [
                        'country_id' => $country->id,
                        'iso_code' => $state['iso_code'],
                    ],
                    [
                        'name' => $state['name'],
                        'iso_numeric' => $state['iso_numeric'] ?? null,
                        'calling_code' => $state['calling_code'] ?? null,
                        'active' => $state['active'] ?? 1,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                $seededStates++;
            }

            Cache::forget('states_pluck_'.$country->id);
            $seededCountries++;
        }

        $this->command?->info("States seeded for {$seededCountries} countries ({$seededStates} state rows). Skipped {$skippedCountries} files with no matching country.");
    }
}

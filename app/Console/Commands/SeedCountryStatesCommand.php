<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeedCountryStatesCommand extends Command
{
    protected $signature = 'states:seed {country=IN : ISO country code (e.g. IN, MZ, US)}';

    protected $description = 'Seed or refresh states for a single country from database/seeders/data/states/{code}.json';

    public function handle(): int
    {
        $countryCode = strtoupper((string) $this->argument('country'));
        $file = database_path("seeders/data/states/{$countryCode}.json");

        if (! is_file($file)) {
            $this->error("State file not found: {$file}");

            return self::FAILURE;
        }

        $country = DB::table('countries')->where('iso_code', $countryCode)->first();

        if (! $country) {
            $this->error("Country with iso_code [{$countryCode}] not found in the countries table.");
            $this->line('Add the country first (admin → settings → countries) or run CountriesSeeder on a fresh database.');

            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($file), true);

        if (! is_array($json)) {
            $this->error("Invalid JSON in {$file}");

            return self::FAILURE;
        }

        $now = Carbon::now();
        $count = 0;

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

            $count++;
        }

        Cache::forget('states_pluck_'.$country->id);

        $this->info("Seeded {$count} states for {$country->name} ({$countryCode}).");
        $this->line('Run: php artisan cache:clear');

        return self::SUCCESS;
    }
}

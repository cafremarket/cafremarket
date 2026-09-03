<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminOnlyUserSeeder extends BaseSeeder
{
    /**
     * Create a single platform super-admin (user id 1).
     */
    public function run(): void
    {
        $email = $this->resolveCredential('email', 'FRESH_ADMIN_EMAIL', 'admin@cafrepay.com');
        $password = $this->resolveCredential('password', 'FRESH_ADMIN_PASSWORD', 'password');
        $name = $this->resolveCredential('name', 'FRESH_ADMIN_NAME', 'Admin');

        DB::table('users')->insert([
            'id' => 1,
            'shop_id' => null,
            'role_id' => Role::SUPER_ADMIN,
            'nice_name' => 'Admin',
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'active' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('dashboard_configs')->insert([
            'user_id' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $country = DB::table('countries')->orderBy('id')->first();
        $state = $country
            ? DB::table('states')->where('country_id', $country->id)->orderBy('id')->first()
            : null;

        if ($country) {
            DB::table('addresses')->insert([
                'address_type' => 'Primary',
                'addressable_type' => \App\Models\User::class,
                'addressable_id' => 1,
                'address_title' => 'Primary Address',
                'state_id' => $state?->id,
                'country_id' => $country->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->command?->info("Admin user created: {$email}");
    }

    protected function resolveCredential(string $configKey, string $envKey, string $default): string
    {
        $value = config('fresh_admin.'.$configKey);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        $env = $_ENV[$envKey] ?? $_SERVER[$envKey] ?? getenv($envKey);

        if (is_string($env) && $env !== '') {
            return $env;
        }

        return $default;
    }
}

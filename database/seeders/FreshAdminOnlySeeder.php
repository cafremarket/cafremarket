<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;

/**
 * Wipes the database (via migrate:fresh) and seeds only platform essentials
 * plus one super-admin user. Run through the artisan command:
 *
 *   php artisan cafrepay:fresh-admin-only --force
 */
class FreshAdminOnlySeeder extends BaseSeeder
{
    /**
     * Minimal seeders required for the admin panel to boot.
     *
     * @var array<int, class-string<BaseSeeder>>
     */
    protected array $essentialSeeders = [
        TimezonesSeeder::class,
        CurrenciesSeeder::class,
        CountriesSeeder::class,
        StatesSeeder::class,
        RolesSeeder::class,
        SystemsSeeder::class,
        ModulesSeeder::class,
        PermissionSeeder::class,
        PaymentMethodsSeeder::class,
        AddressTypesSeeder::class,
        LanguagesSeeder::class,
        TicketCategoriesSeeder::class,
        DisputeTypesSeeder::class,
        CancellationReasonSeeder::class,
        SubscriptionPlansSeeder::class,
        GtinSeeder::class,
        AdminOnlyUserSeeder::class,
    ];

    public function run(): void
    {
        Model::unguard();

        foreach ($this->essentialSeeders as $seederClass) {
            $this->call($seederClass);
        }

        $email = config('fresh_admin.email', env('FRESH_ADMIN_EMAIL', 'admin@cafrepay.com'));

        $this->command?->newLine();
        $this->command?->info('Fresh admin-only database seeded successfully.');
        $this->command?->table(
            ['Setting', 'Value'],
            [
                ['Admin ID', '1'],
                ['Admin email', $email],
                ['Admin login', url('/admin/login')],
                ['Shops', '0'],
                ['Customers', '0'],
                ['Orders', '0'],
            ]
        );

        Model::reguard();
    }
}

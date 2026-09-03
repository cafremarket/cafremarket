<?php

namespace App\Console\Commands;

use Database\Seeders\FreshAdminOnlySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FreshAdminOnlyCommand extends Command
{
    protected $signature = 'cafrepay:fresh-admin-only
                            {--force : Required — permanently drops all tables and data}
                            {--email=admin@cafrepay.com : Super-admin email}
                            {--password=password : Super-admin password}
                            {--name=Admin : Super-admin display name}';

    protected $description = 'Drop all tables, re-run migrations, and seed platform essentials with one admin user only';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('This will permanently delete ALL database data.');
            $this->line('Run with --force to confirm:');
            $this->line('  php artisan cafrepay:fresh-admin-only --force');
            $this->newLine();
            $this->line('Optional credentials:');
            $this->line('  php artisan cafrepay:fresh-admin-only --force --email=admin@example.com --password=secret --name="Platform Admin"');

            return self::FAILURE;
        }

        $email = trim((string) $this->option('email'));
        $password = (string) $this->option('password');
        $name = trim((string) $this->option('name'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid --email address.');

            return self::FAILURE;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters (--password).');

            return self::FAILURE;
        }

        if ($name === '') {
            $name = 'Admin';
        }

        $this->warn('This will wipe the entire database and create one admin user.');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Email', $email],
                ['Password', str_repeat('*', max(6, strlen($password)))],
                ['Name', $name],
                ['Login URL', url('/admin/login')],
            ]
        );

        if (! $this->confirm('Continue?', true)) {
            $this->comment('Cancelled.');

            return self::FAILURE;
        }

        // Persist credentials for seeders (runtime config can be lost across nested artisan calls).
        $this->publishFreshAdminCredentials($email, $password, $name);

        $this->warn('Dropping all tables and re-running migrations…');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->output->write(Artisan::output());

        // Re-publish after migrate:fresh in case packages reset runtime config.
        $this->publishFreshAdminCredentials($email, $password, $name);

        $this->warn('Seeding platform essentials and admin user…');
        $seeder = new FreshAdminOnlySeeder;
        $seeder->setCommand($this);
        $seeder->run();

        Artisan::call('cache:clear');
        $this->output->write(Artisan::output());

        $this->newLine();
        $this->info('Done. Login at: '.url('/admin/login'));
        $this->line("Email: {$email}");
        $this->comment('Change the admin password after first login.');

        return self::SUCCESS;
    }

    protected function publishFreshAdminCredentials(string $email, string $password, string $name): void
    {
        config([
            'fresh_admin.email' => $email,
            'fresh_admin.password' => $password,
            'fresh_admin.name' => $name,
        ]);

        foreach ([
            'FRESH_ADMIN_EMAIL' => $email,
            'FRESH_ADMIN_PASSWORD' => $password,
            'FRESH_ADMIN_NAME' => $name,
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

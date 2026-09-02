<?php

namespace App\Console\Commands;

use Database\Seeders\FreshAdminOnlySeeder;
use Illuminate\Console\Command;

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
            $this->line('  php artisan cafrepay:fresh-admin-only --force --email=you@example.com --password=secret');

            return self::FAILURE;
        }

        if (! $this->confirm('This will wipe the entire database. Continue?', false)) {
            $this->comment('Cancelled.');

            return self::FAILURE;
        }

        config([
            'fresh_admin' => [
                'email' => $this->option('email'),
                'password' => $this->option('password'),
                'name' => $this->option('name'),
            ],
        ]);

        $this->warn('Dropping all tables and re-running migrations…');
        $this->call('migrate:fresh', ['--force' => true]);

        $this->warn('Seeding platform essentials and admin user…');
        $this->call('db:seed', [
            '--class' => FreshAdminOnlySeeder::class,
            '--force' => true,
        ]);

        $this->call('cache:clear');

        $this->newLine();
        $this->comment('Change the admin password after first login.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FixAdminCredentialsCommand extends Command
{
    protected $signature = 'cafrepay:fix-admin
                            {--email= : Admin email to set}
                            {--password= : Admin password to set}
                            {--name= : Admin display name}';

    protected $description = 'Update or create the platform super-admin login credentials without wiping the database';

    public function handle(): int
    {
        $email = trim((string) ($this->option('email') ?: 'admin@cafrepay.com'));
        $password = (string) ($this->option('password') ?: 'password');
        $name = trim((string) ($this->option('name') ?: 'Platform Admin'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid --email address.');

            return self::FAILURE;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');

            return self::FAILURE;
        }

        $admin = User::query()
            ->where('role_id', Role::SUPER_ADMIN)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            $admin = new User;
            $admin->id = 1;
            $admin->role_id = Role::SUPER_ADMIN;
            $admin->shop_id = null;
        }

        $admin->name = $name;
        $admin->nice_name = 'Admin';
        $admin->email = $email;
        $admin->password = $password; // hashed by User mutator
        $admin->active = true;
        $admin->save();

        $this->info('Admin credentials updated.');
        $this->table(
            ['Setting', 'Value'],
            [
                ['ID', $admin->id],
                ['Email', $admin->email],
                ['Password', $password],
                ['Login', url('/admin/login')],
                ['Hash check', Hash::check($password, $admin->fresh()->password) ? 'OK' : 'FAILED'],
            ]
        );

        return self::SUCCESS;
    }
}

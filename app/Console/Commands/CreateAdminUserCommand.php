<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'admin:create-user
        {username : Username used for admin login}
        {password : Password used for admin login}
        {--name= : Display name}
        {--email= : Email address}
        {--role=admin : Role to assign (admin or manager)}';

    protected $description = 'Create or update an admin-panel user explicitly outside migrations';

    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));
        $password = (string) $this->argument('password');
        $role = strtolower(trim((string) $this->option('role')));

        if (! in_array($role, User::roleOptions(), true)) {
            $this->error('Role must be one of: ' . implode(', ', User::roleOptions()));

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        if ($name === '') {
            $name = ucfirst($username);
        }

        $email = trim((string) $this->option('email'));
        if ($email === '') {
            $email = sprintf('%s@supernumber.local', $username);
        }

        $user = User::query()->updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'is_active' => true,
                'password' => $password,
            ]
        );

        $this->info(sprintf('Saved %s user [%s] with username [%s].', $user->role, $user->name, $user->username));

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('pakelagi.admin.email');
        $password = config('pakelagi.admin.password');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL atau ADMIN_PASSWORD belum diisi, akun admin dilewati.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => config('pakelagi.admin.name'), 'password' => $password],
        );
    }
}
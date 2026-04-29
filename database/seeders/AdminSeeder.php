<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@farmersmarket.com'],
            [
                'username' => 'Super Admin',
                'password' => Hash::make('Admin@2026!'),
                'role' => RoleEnum::ADMIN,
                'is_active' => true,
            ]
        );
    }
}
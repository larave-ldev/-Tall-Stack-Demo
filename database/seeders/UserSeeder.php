<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'role_id' => Role::ADMIN,
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin@123'),
        ]);
        //User::factory()->count(100)->create();
    }
}

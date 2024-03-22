<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'nickname' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345'),
        ]);

        $adminRole = Role::where('name', 'Administrator')->first();
        $user->assignRole($adminRole);
    }
}

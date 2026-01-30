<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Buat Akun Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123'), // Password: admin123
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        
        // 2. Buat Akun Publisher Contoh
        User::create([
            'name' => 'Indie Developer',
            'email' => 'publisher@game.com',
            'password' => Hash::make('admin123'), // Password: admin123
            'role' => 'publisher',
            'publisher_request_status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }
}
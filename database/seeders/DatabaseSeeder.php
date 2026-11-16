<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\support\facades\hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    // public function run(): void
    // {
    //     // User::factory(10)->create();

    //     User::factory()->create([
    //         'name' => 'Test User',
    //         'email' => 'test@example.com',
    //     ]);
    // }
    public function run(): void
{
    $akun = [
        ['umum@demo.test', 'Pengguna Umum', 'pengguna_umum'],
        ['keuangan@demo.test', 'Staf Keuangan', 'staf_keuangan'],
        ['admin@demo.test', 'Admin', 'admin'],
    ];

    foreach ($akun as [$email, $nama, $peran]) {
        User::updateOrCreate(
            ['email' => $email],
            ['name' => $nama, 'password' => Hash::make('password'), 'peran' => $peran, 'aktif' => true]
        );
    }
}
}
    

<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tiga akun contoh (kata sandi demo: password)
        $akun = [
            ['umum@demo.test', 'Pengguna Umum', 'pengguna_umum'],
            ['keuangan@demo.test', 'Staf Keuangan', 'staf_keuangan'],
            ['admin@demo.test', 'Admin', 'admin'],
        ];

        foreach ($akun as [$email, $nama, $peran]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => Hash::make('password'),
                    'peran' => $peran,
                    'aktif' => true,
                ]
            );
        }

        // Pengaturan awal. tanggal_acuan kosong = pakai tanggal hari ini.
        $pengaturan = [
            'ambang_hari' => '3',
            'tanggal_acuan' => null,
            'daftar_bank' => 'BRI,Mandiri,BNI,BCA',
            'data_per' => date('Y-m-d'),
        ];

        foreach ($pengaturan as $kunci => $nilai) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }

        $this->call(DataDummySeeder::class);
    }
}

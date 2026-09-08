<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        // Pastikan fakultas tersedia agar foreign key valid
        $fakultasTeknik = Faculty::firstOrCreate(
            ['code' => 'FT'],
            [
                'name' => 'Fakultas Teknik',
                'dean' => 'Dr. Ir. Budi Santoso, M.T.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 1. KELOMPOK ADMIN
        |--------------------------------------------------------------------------
        */

        // Super Admin
        // Jabatan: Kepala Sarpras
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'super_admin',
            'position' => 'Kepala Sarpras',
            'nip' => '1000000001',
            'faculty_id' => null,
            'department' => null,
            'phone_number' => '081234567890',
            'status' => 'active',
        ]);

        // Admin Fakultas Teknik
        // Jabatan: Dekan Fakultas
        User::create([
            'name' => 'Admin Fakultas Teknik',
            'email' => 'admin.ft@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'admin_fakultas',
            'position' => 'Dekan Fakultas',
            'nip' => '1000000002',
            'faculty_id' => $fakultasTeknik->id,
            'department' => null,
            'phone_number' => '081234567891',
            'status' => 'active',
        ]);

        // SDM / Pengadaan Aset
        // Jabatan: Staff SDM & Aset
        User::create([
            'name' => 'Tim SDM & Aset',
            'email' => 'sdm@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'sdm',
            'position' => 'Staff SDM & Aset',
            'nip' => '1000000003',
            'faculty_id' => null,
            'department' => null,
            'phone_number' => '081234567892',
            'status' => 'active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. KELOMPOK USER
        |--------------------------------------------------------------------------
        */

        // Dosen
        User::create([
            'name' => 'Dr. Ahmad Fauzi, M.Kom',
            'email' => 'dosen@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'dosen',
            'position' => 'Dosen',
            'nip' => '0415088501',
            'faculty_id' => $fakultasTeknik->id,
            'department' => 'Teknik Informatika',
            'phone_number' => '081234567893',
            'status' => 'active',
        ]);

        // Mahasiswa Utama
        User::create([
            'name' => 'Bayu Albar',
            'email' => 'mahasiswa@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'mahasiswa',
            'position' => 'Mahasiswa',
            'nip' => '2026010001',
            'faculty_id' => $fakultasTeknik->id,
            'department' => 'Teknik Informatika',
            'phone_number' => '081234567894',
            'status' => 'active',
        ]);

        // Mahasiswa Baru / Pending
        User::create([
            'name' => 'Mahasiswa Baru',
            'email' => 'pending@unival.ac.id',
            'password' => $defaultPassword,
            'role' => 'mahasiswa',
            'position' => 'Mahasiswa',
            'nip' => '2026010002',
            'faculty_id' => $fakultasTeknik->id,
            'department' => 'Teknik Informatika',
            'phone_number' => '081234567895',
            'status' => 'pending',
        ]);
    }
}
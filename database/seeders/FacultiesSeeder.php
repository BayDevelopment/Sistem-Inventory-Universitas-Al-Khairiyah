<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacultiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Faculty::create([
            'id' => 1,
            'name' => 'Fakultas Teknik',
            'code' => 'FT',
        ]);

        Faculty::create([
            'id' => 2,
            'name' => 'Fakultas Ekonomi dan Bisnis',
            'code' => 'FEB',
        ]);
    }
}

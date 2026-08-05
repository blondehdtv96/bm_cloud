<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'level' => 100],
            ['name' => 'ICT', 'slug' => 'ict', 'level' => 90],
            ['name' => 'Kepala Sekolah', 'slug' => 'kepala_sekolah', 'level' => 80],
            ['name' => 'Wakasek', 'slug' => 'wakasek', 'level' => 70],
            ['name' => 'Guru', 'slug' => 'guru', 'level' => 50],
            ['name' => 'TU', 'slug' => 'tu', 'level' => 40],
            ['name' => 'Siswa', 'slug' => 'siswa', 'level' => 10],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}

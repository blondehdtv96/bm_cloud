<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Folder;
use App\Models\File;
use App\Models\Favorite;
use App\Models\Activity;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        if (!$admin) return;

        // Folders
        $folder1 = Folder::create(['user_id' => $admin->id, 'name' => 'Dokumen Penting', 'path' => '/Dokumen Penting', 'parent_id' => null]);
        $folder2 = Folder::create(['user_id' => $admin->id, 'name' => 'Laporan Keuangan', 'path' => '/Laporan Keuangan', 'parent_id' => null]);

        // Files
        $file1 = File::create([
            'user_id' => $admin->id,
            'folder_id' => $folder1->id,
            'original_name' => 'SK_Pengangkatan.pdf',
            'stored_name' => Str::uuid(),
            'hash' => hash('sha256', 'dummy'),
            'mime_type' => 'application/pdf',
            'size' => 1024 * 1024 * 2.5, // 2.5 MB
        ]);

        $file2 = File::create([
            'user_id' => $admin->id,
            'folder_id' => $folder1->id,
            'original_name' => 'Logo_SMKBM.png',
            'stored_name' => Str::uuid(),
            'hash' => hash('sha256', 'dummy2'),
            'mime_type' => 'image/png',
            'size' => 1024 * 500, // 500 KB
        ]);

        // Favorites
        Favorite::create(['user_id' => $admin->id, 'favoritable_type' => 'App\Models\Folder', 'favoritable_id' => $folder1->id]);
        Favorite::create(['user_id' => $admin->id, 'favoritable_type' => 'App\Models\File', 'favoritable_id' => $file1->id]);

        // Activities
        Activity::create([
            'user_id' => $admin->id,
            'action' => 'upload',
            'subject_type' => 'App\Models\File',
            'subject_id' => $file1->id,
            'details' => ['filename' => 'SK_Pengangkatan.pdf', 'size' => '2.5 MB'],
            'ip_address' => '127.0.0.1'
        ]);

        Activity::create([
            'user_id' => $admin->id,
            'action' => 'login',
            'details' => ['device' => 'Chrome / Windows 11'],
            'ip_address' => '127.0.0.1'
        ]);
    }
}

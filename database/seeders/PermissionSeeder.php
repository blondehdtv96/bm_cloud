<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Read Users', 'slug' => 'users.read', 'group' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            // Folders
            ['name' => 'Create Folders', 'slug' => 'folders.create', 'group' => 'folders'],
            ['name' => 'Read Folders', 'slug' => 'folders.read', 'group' => 'folders'],
            ['name' => 'Update Folders', 'slug' => 'folders.update', 'group' => 'folders'],
            ['name' => 'Delete Folders', 'slug' => 'folders.delete', 'group' => 'folders'],
            // Files
            ['name' => 'Create Files', 'slug' => 'files.create', 'group' => 'files'],
            ['name' => 'Read Files', 'slug' => 'files.read', 'group' => 'files'],
            ['name' => 'Update Files', 'slug' => 'files.update', 'group' => 'files'],
            ['name' => 'Delete Files', 'slug' => 'files.delete', 'group' => 'files'],
            ['name' => 'Download Files', 'slug' => 'files.download', 'group' => 'files'],
            // Shares
            ['name' => 'Create Shares', 'slug' => 'shares.create', 'group' => 'shares'],
            ['name' => 'Read Shares', 'slug' => 'shares.read', 'group' => 'shares'],
            ['name' => 'Delete Shares', 'slug' => 'shares.delete', 'group' => 'shares'],
            // Backup
            ['name' => 'Create Backup', 'slug' => 'backup.create', 'group' => 'backup'],
            ['name' => 'Read Backup', 'slug' => 'backup.read', 'group' => 'backup'],
            ['name' => 'Restore Backup', 'slug' => 'backup.restore', 'group' => 'backup'],
            // Activity
            ['name' => 'View Activity', 'slug' => 'activity.view', 'group' => 'activity'],
            // Admin
            ['name' => 'Admin Access', 'slug' => 'admin.access', 'group' => 'admin'],
            // Drive oversight (read-only access to other users' drives)
            ['name' => 'Monitor All Drives', 'slug' => 'drive.monitor', 'group' => 'drive'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // Assign all permissions to super_admin
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->sync(Permission::all());
        }

        // Assign most to ict (example setup, adjust as needed)
        $ict = Role::where('slug', 'ict')->first();
        if ($ict) {
            $ictPerms = Permission::whereIn('group', ['users', 'folders', 'files', 'shares', 'backup', 'activity', 'admin'])
                ->where('slug', '!=', 'backup.restore') // ICT maybe can't restore? Just an example.
                ->get();
            $ict->permissions()->sync($ictPerms);
        }

        // Non-admin roles: everyone can manage their own files/folders/shares and view activity.
        $baseUserPerms = Permission::whereIn('slug', [
            'folders.create', 'folders.read', 'folders.update', 'folders.delete',
            'files.create', 'files.read', 'files.update', 'files.delete', 'files.download',
            'shares.create', 'shares.read', 'shares.delete',
            'activity.view',
        ])->get();

        foreach (['kepala_sekolah', 'wakasek', 'guru', 'tu', 'siswa'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->sync($baseUserPerms);
            }
        }

        // Kepala Sekolah additionally gets read-only oversight of everyone's drive.
        $kepalaSekolah = Role::where('slug', 'kepala_sekolah')->first();
        if ($kepalaSekolah) {
            $monitorPerm = Permission::where('slug', 'drive.monitor')->first();
            if ($monitorPerm) {
                $kepalaSekolah->permissions()->syncWithoutDetaching([$monitorPerm->id]);
            }
        }
    }
}

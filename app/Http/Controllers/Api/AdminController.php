<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Activity;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        $storageByRole = Role::with('users:id')->get()
            ->map(function (Role $role) {
                $userIds = $role->users->pluck('id');
                return [
                    'name' => $role->name,
                    'users_count' => $userIds->count(),
                    'storage' => (int) ($userIds->isEmpty() ? 0 : File::whereIn('user_id', $userIds)->sum('size')),
                ];
            })
            ->sortByDesc('storage')
            ->values();

        return response()->json([
            'total_users' => User::count(),
            'total_files' => File::count(),
            'total_folders' => Folder::count(),
            'storage_used' => (int) File::sum('size'),
            'storage_available' => disk_free_space(storage_path('app')),
            'active_users' => User::where('updated_at', '>=', now()->subDays(30))->count(),
            'storage_by_role' => $storageByRole,
        ]);
    }

    public function logs(Request $request)
    {
        return response()->json(Activity::with('user')->latest()->paginate(50));
    }
}
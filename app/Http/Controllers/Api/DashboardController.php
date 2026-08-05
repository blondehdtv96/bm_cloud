<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregated overview for the authenticated user's personal workspace.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        $totalFiles = File::where('user_id', $userId)->count();
        $totalFolders = Folder::where('user_id', $userId)->count();
        $sharedByMe = Share::where('shared_by', $userId)->count();

        $recentFiles = File::where('user_id', $userId)
            ->latest()
            ->take(6)
            ->get(['id', 'original_name', 'mime_type', 'size', 'folder_id', 'created_at', 'updated_at']);

        $recentActivities = Activity::where('user_id', $userId)
            ->latest()
            ->take(6)
            ->get(['id', 'action', 'subject_type', 'subject_id', 'details', 'created_at']);

        return response()->json([
            'stats' => [
                'total_files' => $totalFiles,
                'total_folders' => $totalFolders,
                'shared_by_me' => $sharedByMe,
                'storage_used' => (int) ($user->storage_used ?? 0),
                'storage_quota' => (int) ($user->storage_quota ?? 0),
            ],
            'recent_files' => $recentFiles,
            'recent_activities' => $recentActivities,
        ]);
    }
}

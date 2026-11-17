<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\ArchiveLimitException;
use App\Services\ArchiveService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;

/**
 * Read-only oversight of other users' drives.
 * Intended for roles with the "drive.monitor" permission (e.g. Kepala Sekolah)
 * so they can review what teachers/staff have stored without being able to
 * modify, delete, or upload anything on their behalf.
 */
class DriveMonitorController extends Controller
{
    protected FileStorageService $storageService;
    protected ActivityService $activityService;

    public function __construct(FileStorageService $storageService, ActivityService $activityService)
    {
        $this->storageService = $storageService;
        $this->activityService = $activityService;
    }

    /**
     * List all users whose drive can be browsed, with basic storage stats.
     */
    public function users(Request $request)
    {
        $viewer = $request->user();

        $users = User::query()
            ->where('id', '!=', $viewer->id)
            ->with('roles')
            ->withCount(['files', 'folders'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
                    'storage_used' => $user->storage_used,
                    'storage_quota' => $user->storage_quota,
                    'files_count' => $user->files_count,
                    'folders_count' => $user->folders_count,
                ];
            });

        return response()->json($users);
    }

    /**
     * Browse the contents of a specific folder (or root) belonging to $userId.
     */
    public function contents(Request $request, $userId)
    {
        $targetUser = User::findOrFail($userId);
        $folderId = $request->query('folder_id');

        $folders = Folder::where('user_id', $targetUser->id)->where('parent_id', $folderId)->get();
        $files = File::where('user_id', $targetUser->id)->where('folder_id', $folderId)->get();

        $this->activityService->log(
            $request->user()->id,
            'monitored_drive',
            $targetUser,
            ['folder_id' => $folderId]
        );

        return response()->json([
            'user' => ['id' => $targetUser->id, 'name' => $targetUser->name, 'email' => $targetUser->email],
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    /**
     * Breadcrumb trail for a folder inside another user's drive.
     */
    public function folderTrail(Request $request, $userId, $folderId)
    {
        $folder = Folder::where('user_id', $userId)->findOrFail($folderId);

        $trail = [];
        $current = $folder;
        while ($current) {
            array_unshift($trail, ['id' => $current->id, 'name' => $current->name]);
            $current = $current->parent_id
                ? Folder::where('user_id', $userId)->find($current->parent_id)
                : null;
        }

        return response()->json(['folder' => $folder, 'trail' => $trail]);
    }

    /**
     * Read-only preview stream of another user's file (no download log tampering, view only).
     */
    public function previewFile(Request $request, $userId, $fileId)
    {
        $file = File::where('user_id', $userId)->findOrFail($fileId);

        $this->activityService->log($request->user()->id, 'monitored_file_preview', $file);

        return $this->storageService->preview($file);
    }

    /**
     * Read-only download of another user's file.
     */
    public function downloadFile(Request $request, $userId, $fileId)
    {
        $file = File::where('user_id', $userId)->findOrFail($fileId);

        $this->activityService->log($request->user()->id, 'monitored_file_download', $file);

        return $this->storageService->download($file);
    }

    /**
     * Read-only ZIP download of several files/folders from another user's drive.
     * Folders are included recursively with their sub-folder structure.
     */
    public function downloadArchive(Request $request, ArchiveService $archiveService, $userId)
    {
        $targetUser = User::findOrFail($userId);
        $data = $request->validate([
            'file_ids' => ['array', 'max:'.ArchiveService::MAX_FILES],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['array', 'max:500'],
            'folder_ids.*' => ['integer'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        $fileIds = array_values(array_unique($data['file_ids'] ?? []));
        $folderIds = array_values(array_unique($data['folder_ids'] ?? []));
        if (! $fileIds && ! $folderIds) {
            abort(422, 'Pilih minimal satu file atau folder.');
        }

        // Scoped to the monitored user: IDs from anyone else's drive are simply not found.
        $files = File::where('user_id', $targetUser->id)->whereIn('id', $fileIds)->get();
        $folders = Folder::where('user_id', $targetUser->id)->whereIn('id', $folderIds)->get();
        if ($files->count() !== count($fileIds) || $folders->count() !== count($folderIds)) {
            abort(404, 'Sebagian item tidak ditemukan atau sudah dihapus.');
        }

        try {
            $archive = $archiveService->build($files->sortBy('original_name'), $folders->sortBy('name'));
        } catch (ArchiveLimitException $e) {
            abort(422, $e->getMessage());
        }

        $this->activityService->log($request->user()->id, 'monitored_archive_download', $targetUser, [
            'files' => $archive['files'],
            'folders' => $folders->pluck('name')->all(),
            'file_ids' => $files->pluck('id')->all(),
        ]);

        $baseName = trim(preg_replace('/[^\pL\pN _.-]+/u', '', (string) ($data['name'] ?? '')));
        $downloadName = ($baseName !== '' ? $baseName : $targetUser->name.' '.now()->format('Y-m-d')).'.zip';

        return response()->download($archive['path'], $downloadName, [
            'Content-Type' => 'application/zip',
            'X-Archive-Files' => $archive['files'],
            'X-Archive-Missing' => $archive['missing'],
        ])->deleteFileAfterSend(true);
    }
}

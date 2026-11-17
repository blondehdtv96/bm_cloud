<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\ArchiveLimitException;
use App\Services\ArchiveService;
use App\Services\FileStorageService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class FileController extends Controller
{
    protected $storageService;
    protected $activityService;
    protected NotificationService $notificationService;

    public function __construct(
        FileStorageService $storageService,
        ActivityService $activityService,
        NotificationService $notificationService
    ) {
        $this->storageService = $storageService;
        $this->activityService = $activityService;
        $this->notificationService = $notificationService;
    }

    public function upload(StoreFileRequest $request)
    {
        $startedAt = microtime(true);
        $uploadedFile = $request->file('file');
        $userId = (int) $request->user()->id;
        $folderId = $request->filled('folder_id') ? $request->integer('folder_id') : null;
        $context = [
            'user_id' => $userId,
            'folder_id' => $folderId,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => (int) $uploadedFile->getSize(),
        ];

        Log::info('File upload started.', $context);

        try {
            $file = $this->storageService->store($uploadedFile, $userId, $folderId);
        } catch (Throwable $exception) {
            Log::error('File upload failed.', $context + [
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('File upload completed.', $context + [
            'file_id' => $file->id,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        // Peringatan kuota tidak boleh menahan respons 201 upload.
        defer(function () use ($userId) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $this->notificationService->quotaWarning($user);
                }
            } catch (Throwable $exception) {
                Log::warning('Quota notification failed after upload.', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        return response()->json($file, 201);
    }

    /**
     * Upload a new version for an existing file (previous content is preserved in file_versions).
     */
    public function uploadNewVersion(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|max:1048576']);

        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        $file = $this->storageService->replace($file, $request->file('file'), $request->user()->id);

        return response()->json($file);
    }

    /**
     * List version history for a file.
     */
    public function versions(Request $request, $id)
    {
        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($file->versions()->with('createdBy')->orderByDesc('version_number')->get());
    }

    public function download(Request $request, $id)
    {
        $file = File::findOrFail($id);
        $this->authorizeAccess($request, $file);

        $this->activityService->log($request->user()->id ?? $file->user_id, 'downloaded', $file);
        return $this->storageService->download($file);
    }

    /**
     * Stream the file inline so the browser can render a preview (image/pdf/video/audio/text)
     * instead of triggering a download prompt.
     */
    public function preview(Request $request, $id)
    {
        $file = File::findOrFail($id);
        $this->authorizeAccess($request, $file);

        return $this->storageService->preview($file);
    }

    /**
     * Download several files and/or folders as one ZIP archive.
     * Folders are added recursively, keeping their sub-folder structure.
     */
    public function downloadArchive(Request $request, ArchiveService $archiveService)
    {
        $data = $request->validate([
            'file_ids' => ['array', 'max:'.ArchiveService::MAX_FILES],
            'file_ids.*' => ['integer'],
            'folder_ids' => ['array', 'max:200'],
            'folder_ids.*' => ['integer'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        $fileIds = array_values(array_unique($data['file_ids'] ?? []));
        $folderIds = array_values(array_unique($data['folder_ids'] ?? []));
        if (! $fileIds && ! $folderIds) {
            abort(422, 'Pilih minimal satu file atau folder.');
        }

        $user = $request->user();
        $files = File::whereIn('id', $fileIds)->get();
        $folders = Folder::whereIn('id', $folderIds)->get();
        if ($files->count() !== count($fileIds) || $folders->count() !== count($folderIds)) {
            abort(404, 'Sebagian item tidak ditemukan atau sudah dihapus.');
        }
        foreach ($files as $file) {
            $this->authorizeAccess($request, $file);
        }
        foreach ($folders as $folder) {
            if ($folder->user_id !== $user->id && ! $folder->isSharedWith($user)) {
                abort(403, 'Anda tidak memiliki akses ke folder "'.$folder->name.'".');
            }
        }

        try {
            $archive = $archiveService->build($files->sortBy('original_name'), $folders->sortBy('name'));
        } catch (ArchiveLimitException $e) {
            abort(422, $e->getMessage());
        }

        $this->activityService->log($user->id, 'downloaded_archive', null, [
            'files' => $archive['files'],
            'folders' => $folders->pluck('name')->all(),
            'file_ids' => $files->pluck('id')->all(),
        ]);

        $baseName = trim(preg_replace('/[^\pL\pN _.-]+/u', '', (string) ($data['name'] ?? '')));
        $downloadName = ($baseName !== '' ? $baseName : 'BM-Cloud-'.now()->format('Ymd-His')).'.zip';

        return response()->download($archive['path'], $downloadName, [
            'Content-Type' => 'application/zip',
            'X-Archive-Files' => $archive['files'],
            'X-Archive-Missing' => $archive['missing'],
        ])->deleteFileAfterSend(true);
    }

    protected function authorizeAccess(Request $request, File $file): void
    {
        $user = $request->user();
        if ($user && $file->user_id !== $user->id && !$file->isSharedWith($user)) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }
    }

    public function show(Request $request, $id)
    {
        $file = File::findOrFail($id);
        return response()->json($file);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['original_name' => 'required']);
        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        $file->update(['original_name' => $request->original_name]);
        return response()->json($file);
    }

    public function move(Request $request, $id)
    {
        $request->validate(['folder_id' => 'nullable|exists:folders,id']);
        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        $this->storageService->move($file, $request->folder_id);
        return response()->json($file);
    }

    public function copy(Request $request, $id)
    {
        $request->validate(['folder_id' => 'nullable|exists:folders,id']);
        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        $newFile = $this->storageService->copy($file, $request->folder_id);
        return response()->json($newFile);
    }

    public function destroy(Request $request, $id)
    {
        $file = File::where('user_id', $request->user()->id)->findOrFail($id);
        $file->delete(); // Soft delete
        $this->activityService->log($request->user()->id, 'deleted', $file);
        return response()->json(['message' => 'File deleted']);
    }
}
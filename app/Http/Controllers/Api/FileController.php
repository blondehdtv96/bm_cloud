<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Models\User;
use App\Services\ActivityService;
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
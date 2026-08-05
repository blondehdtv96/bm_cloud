<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function store($uploadedFile, $userId, $folderId = null)
    {
        $uuid = (string) Str::uuid();
        $ext = $uploadedFile->getClientOriginalExtension();
        $storedName = $uuid . ($ext ? '.' . $ext : '');
        $path = 'uploads/' . $userId;
        
        $size = $uploadedFile->getSize();
        
        // Update user storage
        $user = \App\Models\User::find($userId);
        $user->storage_used += $size;
        $user->save();

        $uploadedFile->storeAs($path, $storedName, 'local');
        $hash = hash_file('sha256', Storage::disk('local')->path($path . '/' . $storedName));

        $file = File::create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $size,
            'hash' => $hash,
        ]);

        $this->activityService->log($userId, 'uploaded', $file);

        return $file;
    }

    /**
     * Replace the content of an existing file with a new upload.
     * The previous content is preserved as a new row in file_versions before being overwritten.
     */
    public function replace(File $file, $uploadedFile, $userId)
    {
        // 1. Snapshot the current content as a new version record.
        $nextVersionNumber = $file->versions()->max('version_number') + 1;

        \App\Models\FileVersion::create([
            'file_id' => $file->id,
            'version_number' => $nextVersionNumber,
            'stored_name' => $file->stored_name,
            'size' => $file->size,
            'hash' => $file->hash,
            'created_by' => $userId,
        ]);

        // 2. Store the new content under a fresh UUID name (old versioned file stays on disk untouched).
        $uuid = (string) Str::uuid();
        $ext = $uploadedFile->getClientOriginalExtension();
        $newStoredName = $uuid . ($ext ? '.' . $ext : '');
        $path = 'uploads/' . $file->user_id;

        $newSize = $uploadedFile->getSize();
        $sizeDiff = $newSize - $file->size;

        $user = \App\Models\User::find($file->user_id);
        $user->storage_used = max(0, $user->storage_used + $sizeDiff);
        $user->save();

        $uploadedFile->storeAs($path, $newStoredName, 'local');
        $newHash = hash_file('sha256', Storage::disk('local')->path($path . '/' . $newStoredName));

        $file->update([
            'stored_name' => $newStoredName,
            'size' => $newSize,
            'mime_type' => $uploadedFile->getMimeType(),
            'hash' => $newHash,
        ]);

        $this->activityService->log($userId, 'uploaded_new_version', $file, ['version_number' => $nextVersionNumber]);

        return $file->refresh();
    }

    public function delete($file)
    {
        // Actually soft deleted via model, but if permanent:
        $path = 'uploads/' . $file->user_id . '/' . $file->stored_name;
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
        
        $user = \App\Models\User::find($file->user_id);
        $user->storage_used = max(0, $user->storage_used - $file->size);
        $user->save();
        
        $file->forceDelete();
    }

    public function download($file)
    {
        $path = 'uploads/' . $file->user_id . '/' . $file->stored_name;
        return Storage::disk('local')->download($path, $file->original_name);
    }

    /**
     * Stream the file inline (for browser preview) instead of forcing a download.
     */
    public function preview($file)
    {
        $path = 'uploads/' . $file->user_id . '/' . $file->stored_name;

        return Storage::disk('local')->response($path, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ], 'inline');
    }

    public function copy($file, $targetFolderId)
    {
        $uuid = (string) Str::uuid();
        $ext = pathinfo($file->original_name, PATHINFO_EXTENSION);
        $storedName = $uuid . ($ext ? '.' . $ext : '');
        
        $oldPath = 'uploads/' . $file->user_id . '/' . $file->stored_name;
        $newPath = 'uploads/' . $file->user_id . '/' . $storedName;
        
        Storage::disk('local')->copy($oldPath, $newPath);
        
        $user = \App\Models\User::find($file->user_id);
        $user->storage_used += $file->size;
        $user->save();

        $newFile = $file->replicate();
        $newFile->folder_id = $targetFolderId;
        $newFile->stored_name = $storedName;
        $newFile->save();
        
        return $newFile;
    }

    public function move($file, $targetFolderId)
    {
        $file->update(['folder_id' => $targetFolderId]);
        return $file;
    }

    public function getStoragePath()
    {
        return Storage::disk('local')->path('uploads');
    }
}
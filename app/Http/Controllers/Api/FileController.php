<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Services\FileStorageService;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class FileController extends Controller
{
    protected $storageService;
    protected $activityService;

    public function __construct(FileStorageService $storageService, ActivityService $activityService)
    {
        $this->storageService = $storageService;
        $this->activityService = $activityService;
    }

    public function upload(StoreFileRequest $request)
    {
        $file = $this->storageService->store($request->file('file'), $request->user()->id, $request->folder_id);
        return response()->json($file, 201);
    }

    /**
     * Upload a new version for an existing file (previous content is preserved in file_versions).
     */
    public function uploadNewVersion(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|max:102400']);

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
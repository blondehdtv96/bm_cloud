<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use App\Services\FileStorageService;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        $files = File::onlyTrashed()->where('user_id', $request->user()->id)->get();
        $folders = Folder::onlyTrashed()->where('user_id', $request->user()->id)->get();

        return response()->json(['files' => $files, 'folders' => $folders]);
    }

    public function restore(Request $request, $type, $id)
    {
        if ($type === 'file') {
            File::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id)->restore();
        } else {
            Folder::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id)->restore();
        }

        return response()->json(['message' => 'Restored successfully']);
    }

    public function destroy(Request $request, $type, $id, FileStorageService $storageService)
    {
        if ($type === 'file') {
            $file = File::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id);
            $storageService->delete($file);
        } else {
            $folder = Folder::onlyTrashed()->where('user_id', $request->user()->id)->findOrFail($id);
            $folder->forceDelete();
        }

        return response()->json(['message' => 'Permanently deleted']);
    }

    public function empty(Request $request, FileStorageService $storageService)
    {
        $files = File::onlyTrashed()->where('user_id', $request->user()->id)->get();
        foreach ($files as $file) {
            $storageService->delete($file);
        }
        
        Folder::onlyTrashed()->where('user_id', $request->user()->id)->forceDelete();
        
        return response()->json(['message' => 'Trash emptied']);
    }
}
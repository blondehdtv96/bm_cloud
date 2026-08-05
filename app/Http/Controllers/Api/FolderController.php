<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFolderRequest;
use App\Models\Folder;
use App\Models\File;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function index(Request $request)
    {
        $folderId = $request->query('folder_id');
        $userId = $request->user()->id;
        
        $folders = Folder::where('user_id', $userId)->where('parent_id', $folderId)->get();
        $files = File::where('user_id', $userId)->where('folder_id', $folderId)->get();
        
        return response()->json([
            'folders' => $folders,
            'files' => $files
        ]);
    }

    /**
     * Show a single folder along with its ancestor trail (for breadcrumb navigation).
     */
    public function show(Request $request, $id)
    {
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);

        $trail = [];
        $current = $folder;
        while ($current) {
            array_unshift($trail, ['id' => $current->id, 'name' => $current->name]);
            $current = $current->parent_id
                ? Folder::where('user_id', $request->user()->id)->find($current->parent_id)
                : null;
        }

        return response()->json([
            'folder' => $folder,
            'trail' => $trail,
        ]);
    }

    public function store(StoreFolderRequest $request)
    {
        $path = $request->name;
        if ($request->parent_id) {
            $parent = Folder::findOrFail($request->parent_id);
            $path = $parent->path . '/' . $request->name;
        }

        $folder = Folder::create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'path' => $path,
        ]);
        
        $this->activityService->log($request->user()->id, 'created', $folder);
        
        return response()->json($folder, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|max:255']);
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);
        $folder->update(['name' => $request->name]);
        
        // Path updating logic could go here...
        
        return response()->json($folder);
    }

    public function move(Request $request, $id)
    {
        $request->validate(['parent_id' => 'nullable|exists:folders,id']);
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);
        $folder->update(['parent_id' => $request->parent_id]);
        return response()->json($folder);
    }

    public function destroy(Request $request, $id)
    {
        $folder = Folder::where('user_id', $request->user()->id)->findOrFail($id);
        $folder->delete();
        $this->activityService->log($request->user()->id, 'deleted', $folder);
        return response()->json(['message' => 'Folder deleted']);
    }
}
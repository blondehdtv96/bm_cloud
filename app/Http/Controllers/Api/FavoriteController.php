<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with('favoritable')
            ->latest()
            ->get()
            ->filter(fn ($favorite) => $favorite->favoritable !== null)
            ->map(function ($favorite) {
                $item = $favorite->favoritable;
                $isFolder = $favorite->favoritable_type === Folder::class;

                return [
                    'favorite_id' => $favorite->id,
                    'id' => $item->id,
                    'type' => $isFolder ? 'folder' : 'file',
                    'name' => $isFolder ? $item->name : $item->original_name,
                    'size' => $isFolder ? null : $item->formatted_size,
                    'mime_type' => $isFolder ? null : $item->mime_type,
                    'folder_id' => $isFolder ? $item->parent_id : $item->folder_id,
                    'updated_at' => $item->updated_at,
                ];
            })
            ->values();

        return response()->json($favorites);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'favoritable_type' => 'required|in:file,folder',
            'favoritable_id' => 'required|integer'
        ]);

        $type = $request->favoritable_type === 'file' ? File::class : Folder::class;

        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('favoritable_type', $type)
            ->where('favoritable_id', $request->favoritable_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Removed from favorites']);
        } else {
            $fav = Favorite::create([
                'user_id' => $request->user()->id,
                'favoritable_type' => $type,
                'favoritable_id' => $request->favoritable_id,
            ]);
            return response()->json($fav, 201);
        }
    }
}
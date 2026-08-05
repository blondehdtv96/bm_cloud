<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->favorites()->with('favoritable')->get());
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'favoritable_type' => 'required|in:file,folder',
            'favoritable_id' => 'required|integer'
        ]);

        $type = $request->favoritable_type === 'file' ? 'App\Models\File' : 'App\Models\Folder';

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
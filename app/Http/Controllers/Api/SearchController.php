<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q', '');
        $userId = $request->user()->id;

        $files = File::where('user_id', $userId)
            ->where('original_name', 'like', '%' . $q . '%')
            ->get();
            
        $folders = Folder::where('user_id', $userId)
            ->where('name', 'like', '%' . $q . '%')
            ->get();

        return response()->json([
            'files' => $files,
            'folders' => $folders
        ]);
    }
}
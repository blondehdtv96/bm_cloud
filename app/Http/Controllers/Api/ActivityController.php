<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::where('user_id', $request->user()->id)->latest();

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        return response()->json($query->paginate(20));
    }
}
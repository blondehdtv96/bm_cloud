<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareRequest;
use App\Http\Requests\CreateShareLinkRequest;
use App\Services\SharingService;
use App\Services\ActivityService;
use App\Models\File;
use App\Models\Folder;
use App\Models\ShareLink;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShareController extends Controller
{
    protected $sharingService;
    protected $activityService;

    public function __construct(SharingService $sharingService, ActivityService $activityService)
    {
        $this->sharingService = $sharingService;
        $this->activityService = $activityService;
    }

    public function store(ShareRequest $request)
    {
        $shareable = $request->shareable_type === 'file' 
            ? File::findOrFail($request->shareable_id) 
            : Folder::findOrFail($request->shareable_id);

        $share = $this->sharingService->shareWithUser(
            $shareable, 
            $request->user()->id, 
            $request->shared_to, 
            $request->permission
        );

        Notification::create([
            'user_id' => $request->shared_to,
            'type' => 'share',
            'title' => 'New item shared with you',
            'message' => $request->user()->name . ' shared an item with you.',
        ]);

        $this->activityService->log($request->user()->id, 'shared', $shareable);

        return response()->json($share, 201);
    }

    public function index(Request $request)
    {
        $shares = $this->sharingService->getSharedWithMe($request->user()->id);
        return response()->json($shares);
    }

    public function createLink(CreateShareLinkRequest $request)
    {
        $shareable = $request->shareable_type === 'file' 
            ? File::findOrFail($request->shareable_id) 
            : Folder::findOrFail($request->shareable_id);

        $link = $this->sharingService->createPublicLink(
            $shareable,
            $request->user()->id,
            $request->password,
            $request->expires_at
        );

        return response()->json($link, 201);
    }

    public function destroy($id)
    {
        $this->sharingService->revokeShare($id);
        return response()->json(['message' => 'Share revoked']);
    }

    public function accessLink(Request $request, $token)
    {
        $link = ShareLink::where('token', $token)->firstOrFail();

        if ($link->expires_at && now()->greaterThan($link->expires_at)) {
            return response()->json(['message' => 'Link expired'], 403);
        }

        if ($link->password) {
            if (!$request->has('password') || !Hash::check($request->password, $link->password)) {
                return response()->json(['message' => 'Invalid password'], 401);
            }
        }

        $link->increment('download_count');
        
        $modelClass = $link->shareable_type === 'file' ? File::class : Folder::class;
        $shareable = $modelClass::findOrFail($link->shareable_id);

        return response()->json($shareable);
    }
}
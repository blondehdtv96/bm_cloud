<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareRequest;
use App\Http\Requests\CreateShareLinkRequest;
use App\Services\SharingService;
use App\Services\ActivityService;
use App\Services\NotificationService;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use App\Models\ShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShareController extends Controller
{
    protected $sharingService;
    protected $activityService;
    protected NotificationService $notificationService;

    public function __construct(
        SharingService $sharingService,
        ActivityService $activityService,
        NotificationService $notificationService
    ) {
        $this->sharingService = $sharingService;
        $this->activityService = $activityService;
        $this->notificationService = $notificationService;
    }

    public function store(ShareRequest $request)
    {
        $shareable = $this->resolveOwnedShareable($request->shareable_type, $request->shareable_id, $request->user());

        if ((int) $request->shared_to === $request->user()->id) {
            return response()->json(['message' => 'Anda tidak bisa membagikan item kepada diri sendiri.'], 422);
        }

        $share = $this->sharingService->shareWithUser(
            $shareable, 
            $request->user()->id, 
            $request->shared_to, 
            $request->permission
        );

        $this->notificationService->shareReceived($share, $request->user(), $shareable);

        $this->activityService->log($request->user()->id, 'shared', $shareable);

        return response()->json($share->load('sharedTo:id,name,email'), 201);
    }

    public function index(Request $request)
    {
        $shares = $this->sharingService->getSharedWithMe($request->user()->id);
        return response()->json($shares);
    }

    /**
     * Isi (subfolder + file) dari sebuah folder yang dibagikan kepada pengguna,
     * dipakai saat pengguna membuka folder di halaman "Dibagikan dengan Saya".
     * Akses diwarisi dari folder induk yang dibagikan, sehingga subfolder di
     * dalamnya juga bisa dibuka meski tidak dibagikan satu per satu.
     */
    public function folderContents(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);
        $this->authorizeFolderAccess($folder, $request->user());

        $ownerId = $folder->user_id;

        return response()->json([
            'folder' => $folder,
            'folders' => Folder::where('user_id', $ownerId)->where('parent_id', $folder->id)->get(),
            'files' => File::where('user_id', $ownerId)->where('folder_id', $folder->id)->get(),
        ]);
    }

    /**
     * Breadcrumb untuk folder yang dibagikan. Berhenti di folder teratas yang
     * benar-benar dibagikan ke pengguna, supaya struktur folder pemilik di
     * atasnya (yang tidak dibagikan) tidak ikut terekspos.
     */
    public function folderTrail(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);
        $user = $request->user();
        $this->authorizeFolderAccess($folder, $user);

        $trail = [];
        $current = $folder;
        while ($current) {
            array_unshift($trail, ['id' => $current->id, 'name' => $current->name]);

            $isDirectlyShared = $current->shares()->where('shared_to', $user->id)->exists();
            if ($isDirectlyShared || !$current->parent_id) {
                break;
            }
            $current = Folder::find($current->parent_id);
        }

        return response()->json(['folder' => $folder, 'trail' => $trail]);
    }

    protected function authorizeFolderAccess(Folder $folder, $user): void
    {
        if ($folder->user_id === $user->id || $user->isAdmin() || $folder->isSharedWith($user)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke folder ini.');
    }

    /**
     * Daftar orang yang sudah punya akses ke sebuah item, dipakai modal "Bagikan".
     * Hanya pemilik item atau admin yang boleh melihatnya.
     */
    public function itemShares(Request $request, string $type, $id)
    {
        $shareable = $this->resolveOwnedShareable($type, $id, $request->user());
        $shareableType = get_class($shareable);

        return response()->json([
            'shares' => $this->sharingService->getSharesForItem($shareableType, $shareable->id),
            'links' => $this->sharingService->getLinksForItem($shareableType, $shareable->id),
        ]);
    }

    /**
     * Cari calon penerima berdasarkan nama/email untuk pemilih di modal "Bagikan".
     */
    public function searchRecipients(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);

        return response()->json(
            $this->sharingService->searchRecipients($request->user()->id, $request->q)
        );
    }

    public function updatePermission(Request $request, $id)
    {
        $request->validate(['permission' => 'required|in:view,edit']);

        $share = Share::findOrFail($id);
        $this->authorizeShareManagement($share, $request->user());

        $share = $this->sharingService->updatePermission($share->id, $request->permission);

        return response()->json($share->load('sharedTo:id,name,email'));
    }

    public function createLink(CreateShareLinkRequest $request)
    {
        $shareable = $this->resolveOwnedShareable($request->shareable_type, $request->shareable_id, $request->user());

        $link = $this->sharingService->createPublicLink(
            $shareable,
            $request->user()->id,
            $request->password,
            $request->expires_at
        );

        $this->activityService->log($request->user()->id, 'created_share_link', $shareable);

        return response()->json($link, 201);
    }

    public function destroy(Request $request, $id)
    {
        $share = Share::with('shareable')->findOrFail($id);
        $this->authorizeShareManagement($share, $request->user());

        $this->notificationService->shareRevoked($share, $request->user(), $share->shareable);
        $this->sharingService->revokeShare($share->id);

        return response()->json(['message' => 'Share revoked']);
    }

    public function destroyLink(Request $request, $id)
    {
        $link = ShareLink::with('shareable')->findOrFail($id);

        if ($link->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Anda tidak berhak menghapus tautan ini.');
        }

        $link->delete();

        return response()->json(['message' => 'Tautan dihapus']);
    }

    /**
     * Ambil file/folder berdasarkan tipe+id dan pastikan pengguna yang login
     * adalah pemiliknya (atau admin). Hanya pemilik yang boleh membagikan item.
     */
    protected function resolveOwnedShareable(string $type, int $id, $user): File|Folder
    {
        $shareable = $type === 'file' ? File::findOrFail($id) : Folder::findOrFail($id);

        if ($shareable->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Anda hanya bisa membagikan item milik Anda sendiri.');
        }

        return $shareable;
    }

    /**
     * Hanya yang membagikan (shared_by), pemilik item, atau admin yang boleh
     * mengubah permission atau mencabut sebuah share.
     */
    protected function authorizeShareManagement(Share $share, $user): void
    {
        if ($user->isAdmin() || $share->shared_by === $user->id) {
            return;
        }

        $shareable = $share->shareable;
        if ($shareable && $shareable->user_id === $user->id) {
            return;
        }

        abort(403, 'Anda tidak berhak mengelola akses berbagi ini.');
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
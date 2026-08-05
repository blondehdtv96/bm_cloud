<?php
$base = __DIR__;

function make_dir($path) {
    global $base;
    $full = $base . '/' . dirname($path);
    if (!is_dir($full)) {
        mkdir($full, 0777, true);
    }
}

$files = [];

// Services
$files['app/Services/FileStorageService.php'] = <<<'PHP'
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
PHP;

$files['app/Services/SharingService.php'] = <<<'PHP'
<?php

namespace App\Services;

use App\Models\Share;
use App\Models\ShareLink;
use Illuminate\Support\Str;

class SharingService
{
    public function shareWithUser($shareable, $sharedBy, $sharedToUserId, $permission = 'view')
    {
        return Share::updateOrCreate([
            'shareable_type' => get_class($shareable),
            'shareable_id' => $shareable->id,
            'shared_to' => $sharedToUserId,
        ], [
            'shared_by' => $sharedBy,
            'permission' => $permission,
        ]);
    }

    public function createPublicLink($shareable, $userId, $password = null, $expiresAt = null)
    {
        return ShareLink::create([
            'shareable_type' => get_class($shareable),
            'shareable_id' => $shareable->id,
            'token' => Str::random(64),
            'password' => $password ? bcrypt($password) : null,
            'expires_at' => $expiresAt,
            'download_count' => 0,
        ]);
    }

    public function getSharedWithMe($userId)
    {
        return Share::where('shared_to', $userId)->with('shareable')->get();
    }

    public function revokeShare($shareId)
    {
        return Share::destroy($shareId);
    }
}
PHP;

$files['app/Services/ActivityService.php'] = <<<'PHP'
<?php

namespace App\Services;

use App\Models\Activity;

class ActivityService
{
    public function log($userId, $action, $subject = null, $details = null)
    {
        return Activity::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
PHP;

// Middleware
$files['app/Http/Middleware/CheckRole.php'] = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !$request->user()->roles()->whereIn('slug', $roles)->exists()) {
            return response()->json(['message' => 'Unauthorized role.'], 403);
        }

        return $next($request);
    }
}
PHP;

$files['app/Http/Middleware/CheckPermission.php'] = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $hasPermission = $user->roles()->with('permissions')->get()->pluck('permissions')->flatten()->pluck('slug')->intersect($permissions)->isNotEmpty();
        
        if (!$hasPermission) {
            return response()->json(['message' => 'Unauthorized permission.'], 403);
        }

        return $next($request);
    }
}
PHP;

// Form Requests
$files['app/Http/Requests/LoginRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }
}
PHP;

$files['app/Http/Requests/StoreFolderRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:folders,id',
        ];
    }
}
PHP;

$files['app/Http/Requests/StoreFileRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'file' => 'required|file|max:102400',
            'folder_id' => 'nullable|exists:folders,id',
        ];
    }
}
PHP;

$files['app/Http/Requests/ShareRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'shareable_type' => 'required|in:file,folder',
            'shareable_id' => 'required|integer',
            'shared_to' => 'required|exists:users,id',
            'permission' => 'required|in:view,edit',
        ];
    }
}
PHP;

$files['app/Http/Requests/CreateShareLinkRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShareLinkRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'shareable_type' => 'required|in:file,folder',
            'shareable_id' => 'required|integer',
            'password' => 'nullable|min:6',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
PHP;

$files['app/Http/Requests/StoreUserRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'storage_quota' => 'nullable|integer',
        ];
    }
}
PHP;

$files['app/Http/Requests/UpdateUserRequest.php'] = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$this->route('id'),
            'password' => 'nullable|min:8',
            'role_id' => 'sometimes|exists:roles,id',
            'storage_quota' => 'nullable|integer',
        ];
    }
}
PHP;

// Controllers
$files['app/Http/Controllers/Api/AuthController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles.permissions')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles.permissions'));
    }
}
PHP;

$files['app/Http/Controllers/Api/UserController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate(15));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'storage_quota' => $request->storage_quota ?? 10737418240, // default 10GB
            'storage_used' => 0
        ]);
        
        $user->roles()->attach($request->role_id);
        
        return response()->json($user->load('roles'), 201);
    }

    public function show($id)
    {
        return response()->json(User::with('roles')->findOrFail($id));
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        $data = $request->only(['name', 'email', 'storage_quota']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }
        
        $user->update($data);
        
        if ($request->has('role_id')) {
            $user->roles()->sync([$request->role_id]);
        }
        
        return response()->json($user->load('roles'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
PHP;

$files['app/Http/Controllers/Api/FolderController.php'] = <<<'PHP'
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
PHP;

$files['app/Http/Controllers/Api/FileController.php'] = <<<'PHP'
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

    public function download(Request $request, $id)
    {
        $file = File::findOrFail($id);
        
        // Authorization check logic here... (owner or shared)
        
        $this->activityService->log($request->user()->id ?? $file->user_id, 'downloaded', $file);
        return $this->storageService->download($file);
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
PHP;

$files['app/Http/Controllers/Api/ShareController.php'] = <<<'PHP'
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
PHP;

$files['app/Http/Controllers/Api/FavoriteController.php'] = <<<'PHP'
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
PHP;

$files['app/Http/Controllers/Api/TrashController.php'] = <<<'PHP'
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
PHP;

$files['app/Http/Controllers/Api/ActivityController.php'] = <<<'PHP'
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
PHP;

$files['app/Http/Controllers/Api/NotificationController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->notifications()->latest()->paginate(15));
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update(['read_at' => now()]);
        return response()->json($notification);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->update(['read_at' => now()]);
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count();
        return response()->json(['count' => $count]);
    }
}
PHP;

$files['app/Http/Controllers/Api/AdminController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Models\Activity;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_files' => File::count(),
            'total_folders' => Folder::count(),
            'storage_used' => File::sum('size'),
            'storage_available' => disk_free_space(storage_path('app')),
            'active_users' => User::where('updated_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    public function logs(Request $request)
    {
        return response()->json(Activity::with('user')->latest()->paginate(50));
    }
}
PHP;

$files['app/Http/Controllers/Api/BackupController.php'] = <<<'PHP'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use App\Services\ActivityService;

class BackupController extends Controller
{
    public function index()
    {
        $files = Storage::disk('local')->files('backups');
        return response()->json($files);
    }

    public function store(Request $request, ActivityService $activityService)
    {
        // Simple manual backup logic placeholder (or use a package like spatie/laravel-backup)
        $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
        Storage::disk('local')->put('backups/' . $filename, 'dummy backup data');
        
        $activityService->log($request->user()->id, 'created_backup', null, ['filename' => $filename]);
        
        return response()->json(['message' => 'Backup created', 'file' => $filename]);
    }

    public function destroy($id)
    {
        Storage::disk('local')->delete('backups/' . $id);
        return response()->json(['message' => 'Backup deleted']);
    }
}
PHP;

$files['app/Http/Controllers/Api/SearchController.php'] = <<<'PHP'
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
PHP;

$files['routes/api.php'] = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\TrashController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\SearchController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/s/{token}', [ShareController::class, 'accessLink']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    Route::apiResource('folders', FolderController::class);
    Route::post('folders/{id}/move', [FolderController::class, 'move']);
    
    Route::apiResource('files', FileController::class)->except(['store']);
    Route::post('files/upload', [FileController::class, 'upload']);
    Route::get('files/{id}/download', [FileController::class, 'download']);
    Route::post('files/{id}/move', [FileController::class, 'move']);
    Route::post('files/{id}/copy', [FileController::class, 'copy']);
    
    Route::apiResource('shares', ShareController::class)->only(['index', 'store', 'destroy']);
    Route::post('shares/link', [ShareController::class, 'createLink']);
    
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
    
    Route::get('trash', [TrashController::class, 'index']);
    Route::post('trash/restore/{type}/{id}', [TrashController::class, 'restore']);
    Route::delete('trash/{type}/{id}', [TrashController::class, 'destroy']);
    Route::delete('trash/empty', [TrashController::class, 'empty']);
    
    Route::get('activities', [ActivityController::class, 'index']);
    
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    
    Route::get('search', [SearchController::class, 'search']);

    Route::middleware('role:super_admin,ict')->prefix('admin')->group(function () {
        Route::get('stats', [AdminController::class, 'stats']);
        Route::get('logs', [AdminController::class, 'logs']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('backups', BackupController::class)->only(['index', 'store', 'destroy']);
    });
});
PHP;

$files['routes/web.php'] = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
PHP;

$files['resources/views/app.blade.php'] = <<<'PHP'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMKBM Cloud Storage</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
PHP;

$files['bootstrap/app.php'] = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
PHP;

$files['config/cors.php'] = <<<'PHP'
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
PHP;

foreach ($files as $path => $content) {
    make_dir($path);
    file_put_contents($base . '/' . $path, $content);
    echo "Created: $path\n";
}

echo "All files generated successfully.";
?>

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $request->role));
        }

        return response()->json($query->orderBy('name')->paginate(15));
    }

    public function store(StoreUserRequest $request, NotificationService $notificationService)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'storage_quota' => $request->storage_quota ?? 10737418240, // default 10GB
            'storage_used' => 0,
            'status' => $request->status ?? 'active',
        ]);
        
        $user->roles()->attach($request->role_id);

        $notificationService->accountCreated($user, $request->user());

        return response()->json($user->load('roles'), 201);
    }

    public function show($id)
    {
        return response()->json(User::with('roles')->findOrFail($id));
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        
        $data = $request->only(['name', 'email', 'storage_quota', 'status']);
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
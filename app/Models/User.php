<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'storage_quota',
        'storage_used',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id');
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class, 'shared_by');
    }

    public function sharedWithMe()
    {
        return $this->hasMany(Share::class, 'shared_to');
    }

    public function hasRole($roleSlug)
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasPermission($permissionSlug)
    {
        $permissions = $this->permissions();
        return $permissions->contains('slug', $permissionSlug);
    }

    /**
     * True if this user is allowed to browse other users' drives read-only
     * (e.g. Kepala Sekolah oversight).
     */
    public function canMonitorAllDrives()
    {
        return $this->isAdmin() || $this->hasPermission('drive.monitor');
    }

    public function isAdmin()
    {
        return $this->hasRole('super_admin') || $this->hasRole('ict');
    }

    public function storageUsagePercent()
    {
        if ($this->storage_quota <= 0) return 100;
        return round(($this->storage_used / $this->storage_quota) * 100, 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'parent_id', 'name', 'path'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function shares()
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * True if this folder (or any ancestor) has been shared with $user,
     * so opening a subfolder of a shared folder inherits access.
     */
    public function isSharedWith($user)
    {
        $userId = $user->id ?? $user;

        $folder = $this;
        while ($folder) {
            if ($folder->shares()->where('shared_to', $userId)->exists()) {
                return true;
            }
            $folder = $folder->parent_id ? Folder::find($folder->parent_id) : null;
        }

        return false;
    }
}

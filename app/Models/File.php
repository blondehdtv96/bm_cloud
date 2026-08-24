<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'folder_id', 'original_name', 'stored_name',
        'mime_type', 'size', 'hash', 'description'
    ];

    protected $appends = ['formatted_size'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class);
    }

    public function shares()
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function shareLinks()
    {
        return $this->morphMany(ShareLink::class, 'shareable');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * True if this file is shared directly, or sits inside a folder shared with $user.
     */
    public function isSharedWith($user)
    {
        if ($this->shares()->where('shared_to', $user->id ?? $user)->exists()) {
            return true;
        }

        return $this->folder ? $this->folder->isSharedWith($user) : false;
    }

    protected function formattedSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->size;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                $bytes /= pow(1024, $pow);
                return round($bytes, 2) . ' ' . $units[$pow];
            },
        );
    }
}

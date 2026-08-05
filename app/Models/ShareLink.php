<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'shareable_type', 'shareable_id', 'token',
        'password', 'expires_at', 'download_count', 'created_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function shareable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPasswordProtected()
    {
        return !empty($this->password);
    }
}

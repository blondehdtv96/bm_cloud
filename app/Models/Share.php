<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'shareable_type', 'shareable_id',
        'shared_by', 'shared_to', 'permission'
    ];

    public function shareable()
    {
        return $this->morphTo();
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedTo()
    {
        return $this->belongsTo(User::class, 'shared_to');
    }
}

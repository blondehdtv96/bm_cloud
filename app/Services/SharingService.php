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
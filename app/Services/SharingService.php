<?php

namespace App\Services;

use App\Models\Share;
use App\Models\ShareLink;
use App\Models\User;
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

    /**
     * Semua orang yang saat ini punya akses ke satu item (file/folder), dipakai
     * modal "Bagikan" untuk menampilkan daftar dan mengubah/mencabut akses.
     */
    public function getSharesForItem(string $shareableType, int $shareableId)
    {
        return Share::where('shareable_type', $shareableType)
            ->where('shareable_id', $shareableId)
            ->with('sharedTo:id,name,email')
            ->latest()
            ->get();
    }

    public function getLinksForItem(string $shareableType, int $shareableId)
    {
        return ShareLink::where('shareable_type', $shareableType)
            ->where('shareable_id', $shareableId)
            ->latest()
            ->get();
    }

    public function updatePermission(int $shareId, string $permission): Share
    {
        $share = Share::findOrFail($shareId);
        $share->update(['permission' => $permission]);
        return $share;
    }

    /**
     * Kandidat penerima untuk modal "Bagikan": pengguna aktif selain diri sendiri,
     * opsional difilter nama/email. Dipakai lintas peran, bukan hanya admin,
     * jadi hasilnya dibatasi ke kolom yang aman untuk ditampilkan.
     */
    public function searchRecipients(int $excludeUserId, ?string $search, int $limit = 10)
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('id', '!=', $excludeUserId)
            ->where('status', 'active')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
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
        return Share::where('shared_to', $userId)
            ->with(['shareable', 'sharedBy:id,name'])
            ->latest()
            ->get();
    }

    public function revokeShare($shareId)
    {
        return Share::destroy($shareId);
    }
}
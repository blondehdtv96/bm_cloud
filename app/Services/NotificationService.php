<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Notification;
use App\Models\Share;
use App\Models\User;

/**
 * Satu pintu untuk membuat notifikasi in-app.
 *
 * Notifikasi dikonsumsi lewat GET /api/notifications dan lonceng di header.
 * Tipe yang dipakai frontend untuk memilih ikon: share, share_revoked,
 * storage, backup, account.
 */
class NotificationService
{
    /** Ambang peringatan kuota penyimpanan, dalam persen. */
    public const QUOTA_WARNING_PERCENT = 90;

    public function notify(User|int $user, string $type, string $title, string $message, array $data = []): ?Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (!$userId) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }

    /**
     * Kirim notifikasi yang sama ke banyak pengguna sekaligus.
     */
    public function notifyMany(iterable $users, string $type, string $title, string $message, array $data = []): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->notify($user, $type, $title, $message, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Kirim ke semua admin (super_admin dan ict), opsional kecualikan satu pengguna
     * supaya pemicu aksinya tidak menerima notifikasi dari dirinya sendiri.
     */
    public function notifyAdmins(string $type, string $title, string $message, array $data = [], ?int $exceptUserId = null): int
    {
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'ict']))
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->pluck('id');

        return $this->notifyMany($admins, $type, $title, $message, $data);
    }

    // ---------------------------------------------------------------------
    // Pemicu spesifik
    // ---------------------------------------------------------------------

    public function shareReceived(Share $share, User $sharedBy, $shareable): ?Notification
    {
        $isFolder = $share->shareable_type === \App\Models\Folder::class;
        $name = $isFolder ? $shareable->name : $shareable->original_name;

        return $this->notify(
            $share->shared_to,
            'share',
            $isFolder ? 'Folder baru dibagikan' : 'File baru dibagikan',
            sprintf('%s membagikan "%s" kepada Anda.', $sharedBy->name, $name),
            [
                'url' => '/shared',
                'share_id' => $share->id,
                'shareable_type' => $isFolder ? 'folder' : 'file',
                'shareable_id' => $share->shareable_id,
                'permission' => $share->permission,
            ]
        );
    }

    public function shareRevoked(Share $share, User $revokedBy, $shareable): ?Notification
    {
        $isFolder = $share->shareable_type === \App\Models\Folder::class;
        $name = $shareable
            ? ($isFolder ? $shareable->name : $shareable->original_name)
            : 'sebuah item';

        return $this->notify(
            $share->shared_to,
            'share_revoked',
            'Akses dibagikan dicabut',
            sprintf('%s mencabut akses Anda ke "%s".', $revokedBy->name, $name),
            ['url' => '/shared']
        );
    }

    /**
     * Peringatkan pemilik drive saat kuota hampir penuh. Hanya dikirim sekali
     * per 24 jam supaya tidak membanjiri lonceng setiap kali upload.
     */
    public function quotaWarning(User $user): ?Notification
    {
        if ($user->storage_quota <= 0) {
            return null;
        }

        $percent = (int) round(($user->storage_used / $user->storage_quota) * 100);

        if ($percent < self::QUOTA_WARNING_PERCENT) {
            return null;
        }

        $alreadyWarned = Notification::where('user_id', $user->id)
            ->where('type', 'storage')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($alreadyWarned) {
            return null;
        }

        return $this->notify(
            $user,
            'storage',
            $percent >= 100 ? 'Penyimpanan penuh' : 'Penyimpanan hampir penuh',
            sprintf('Kuota drive Anda terpakai %d%%. Hapus file yang tidak perlu atau minta tambahan kuota.', $percent),
            ['url' => '/drive', 'percent' => $percent]
        );
    }

    public function backupFinished(Backup $backup, ?int $triggeredBy = null): int
    {
        $failed = $backup->status === 'failed';

        return $this->notifyAdmins(
            'backup',
            $failed ? 'Backup gagal' : 'Backup selesai',
            $failed
                ? sprintf('Backup "%s" gagal dibuat. Periksa log sistem.', $backup->name)
                : sprintf('Backup "%s" berhasil dibuat.', $backup->name),
            ['url' => '/admin/backup', 'backup_id' => $backup->id, 'status' => $backup->status],
            $triggeredBy
        );
    }

    public function accountCreated(User $user, ?User $createdBy = null): ?Notification
    {
        return $this->notify(
            $user,
            'account',
            'Selamat datang di BM Clouds',
            $createdBy
                ? sprintf('Akun Anda dibuat oleh %s. Lengkapi profil dan ganti kata sandi Anda.', $createdBy->name)
                : 'Akun Anda sudah aktif. Lengkapi profil dan ganti kata sandi Anda.',
            ['url' => '/profile']
        );
    }

    /**
     * Ringkasan jumlah belum dibaca untuk badge di header.
     */
    public function unreadCount(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }
}

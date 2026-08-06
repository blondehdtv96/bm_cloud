<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:run {type=manual : daily|weekly|monthly|manual}';

    protected $description = 'Membuat backup database dan file terupload sesuai jadwal (daily/weekly/monthly).';

    public function handle(BackupService $backupService, NotificationService $notificationService): int
    {
        $type = $this->argument('type');

        $this->info("Membuat backup ($type)...");

        $backup = $backupService->create(null, $type);

        // Backup terjadwal jalan tanpa pengguna, jadi semua admin perlu diberi tahu.
        $notificationService->backupFinished($backup);

        if ($backup->status === 'failed') {
            $this->error('Backup gagal: ' . $backup->error_message);
            return self::FAILURE;
        }

        $this->info("Backup berhasil dibuat: {$backup->name} ({$backup->size} bytes)");
        return self::SUCCESS;
    }
}

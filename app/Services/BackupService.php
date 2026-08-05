<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class BackupService
{
    /**
     * Directory (relative to local disk root) where backup archives are stored.
     */
    protected string $backupDir = 'backups';

    /**
     * Directory (relative to local disk root) where uploaded files live.
     */
    protected string $uploadsDir = 'uploads';

    /**
     * Create a full backup (database dump + uploaded files) and store it as a zip archive.
     */
    public function create(?int $userId = null, string $type = 'manual'): Backup
    {
        $name = 'backup_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $relativePath = $this->backupDir . '/' . $name;

        $backup = Backup::create([
            'name' => $name,
            'path' => $relativePath,
            'size' => 0,
            'type' => $type,
            'status' => 'running',
            'created_by' => $userId,
        ]);

        try {
            Storage::disk('local')->makeDirectory($this->backupDir);
            $absolutePath = Storage::disk('local')->path($relativePath);

            $zip = new ZipArchive();
            if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak bisa membuat file arsip backup.');
            }

            // 1. Database dump
            $dumpContent = $this->dumpDatabase();
            $zip->addFromString('database.sql', $dumpContent);

            // 2. Uploaded files
            $this->addDirectoryToZip($zip, $this->uploadsDir, 'uploads');

            $zip->close();

            $size = @filesize($absolutePath) ?: 0;

            $backup->update([
                'size' => $size,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Clean up partial archive if it exists
            if (Storage::disk('local')->exists($relativePath)) {
                Storage::disk('local')->delete($relativePath);
            }
        }

        return $backup->refresh();
    }

    /**
     * Restore database and uploaded files from a completed backup archive.
     */
    public function restore(Backup $backup): void
    {
        if ($backup->status !== 'completed') {
            throw new RuntimeException('Hanya backup dengan status selesai yang dapat dipulihkan.');
        }

        if (!Storage::disk('local')->exists($backup->path)) {
            throw new RuntimeException('Arsip backup tidak ditemukan di penyimpanan.');
        }

        $absolutePath = Storage::disk('local')->path($backup->path);
        $extractDir = storage_path('app/tmp_restore_' . $backup->id);

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('Gagal membuka arsip backup.');
        }
        $zip->extractTo($extractDir);
        $zip->close();

        // Restore database
        $sqlFile = $extractDir . DIRECTORY_SEPARATOR . 'database.sql';
        if (file_exists($sqlFile)) {
            $this->restoreDatabase(file_get_contents($sqlFile));
        }

        // Restore uploaded files
        $uploadsExtracted = $extractDir . DIRECTORY_SEPARATOR . 'uploads';
        if (is_dir($uploadsExtracted)) {
            $this->copyDirectory($uploadsExtracted, Storage::disk('local')->path($this->uploadsDir));
        }

        $this->deleteDirectory($extractDir);
    }

    /**
     * Remove a backup record and its archive file.
     */
    public function delete(Backup $backup): void
    {
        if (Storage::disk('local')->exists($backup->path)) {
            Storage::disk('local')->delete($backup->path);
        }
        $backup->delete();
    }

    /**
     * Produce a SQL dump string for the current database connection.
     * Supports MySQL/MariaDB (via mysqldump binary) and SQLite (raw file copy as SQL-less binary dump).
     */
    protected function dumpDatabase(): string
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if (!file_exists($path)) {
                throw new RuntimeException('File database SQLite tidak ditemukan.');
            }
            // Store raw sqlite bytes base64-encoded so it can be embedded in the zip as text-safe content.
            return base64_encode(file_get_contents($path));
        }

        if (in_array($connection, ['mysql', 'mariadb'])) {
            $config = config("database.connections.$connection");
            $binary = env('MYSQLDUMP_PATH', 'mysqldump');

            $command = sprintf(
                '%s --host=%s --port=%s --user=%s %s --skip-lock-tables --single-transaction %s',
                escapeshellarg($binary),
                escapeshellarg($config['host']),
                escapeshellarg((string) $config['port']),
                escapeshellarg($config['username']),
                $config['password'] !== '' ? '--password=' . escapeshellarg($config['password']) : '',
                escapeshellarg($config['database'])
            );

            $output = [];
            $exitCode = 0;
            exec($command . ' 2>&1', $output, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException('mysqldump gagal dijalankan: ' . implode("\n", $output));
            }

            return implode("\n", $output);
        }

        throw new RuntimeException("Dump database untuk driver '$connection' belum didukung.");
    }

    protected function restoreDatabase(string $dumpContent): void
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            file_put_contents($path, base64_decode($dumpContent));
            return;
        }

        if (in_array($connection, ['mysql', 'mariadb'])) {
            $statements = array_filter(array_map('trim', explode(";\n", $dumpContent)));
            foreach ($statements as $statement) {
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }
                try {
                    DB::unprepared($statement);
                } catch (\Throwable $e) {
                    // Skip statements that fail (e.g. dump header comments) but keep going.
                    continue;
                }
            }
            return;
        }

        throw new RuntimeException("Restore database untuk driver '$connection' belum didukung.");
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $relativeDir, string $zipPrefix): void
    {
        $fullPath = Storage::disk('local')->path($relativeDir);

        if (!is_dir($fullPath)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $localName = $zipPrefix . '/' . substr($file->getPathname(), strlen($fullPath) + 1);
            $localName = str_replace('\\', '/', $localName);
            $zip->addFile($file->getPathname(), $localName);
        }
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($source) + 1);
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}

<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Packs selected files and folders (recursively, keeping the folder tree)
 * into a temporary ZIP archive for the multi-download feature.
 */
class ArchiveService
{
    /** Hard cap on uncompressed content per archive (2 GB). */
    public const MAX_TOTAL_BYTES = 2 * 1024 * 1024 * 1024;

    /** Hard cap on number of files per archive. */
    public const MAX_FILES = 2000;

    /** @var array<string, true> paths already used inside the archive */
    private array $usedPaths = [];

    private int $totalBytes = 0;

    private int $fileCount = 0;

    private int $missing = 0;

    /**
     * @param  iterable<File>  $files  files at the archive root
     * @param  iterable<Folder>  $folders  folders added recursively as directories
     * @return array{path: string, files: int, missing: int}
     */
    public function build(iterable $files, iterable $folders): array
    {
        $dir = Storage::disk('local')->path('tmp/archives');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('Folder sementara untuk arsip tidak bisa dibuat.');
        }
        $this->purgeStale($dir);

        $zipPath = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Arsip ZIP tidak bisa dibuat.');
        }

        try {
            foreach ($files as $file) {
                $this->addFile($zip, $file, '');
            }
            foreach ($folders as $folder) {
                $this->addFolder($zip, $folder, '');
            }

            if ($this->fileCount === 0) {
                // ZipArchive refuses to write an archive with no entries.
                $zip->addFromString('KOSONG.txt', 'Item yang dipilih tidak berisi file.');
            }
        } catch (\Throwable $e) {
            $zip->close();
            @unlink($zipPath);
            throw $e;
        }

        if (! $zip->close()) {
            @unlink($zipPath);
            throw new RuntimeException('Arsip ZIP gagal disimpan.');
        }

        return ['path' => $zipPath, 'files' => $this->fileCount, 'missing' => $this->missing];
    }

    private function addFolder(ZipArchive $zip, Folder $folder, string $prefix): void
    {
        $dirPath = $this->uniquePath($prefix.$this->sanitize($folder->name), true).'/';
        $zip->addEmptyDir(rtrim($dirPath, '/'));

        foreach ($folder->files()->orderBy('original_name')->get() as $file) {
            $this->addFile($zip, $file, $dirPath);
        }
        foreach ($folder->children()->orderBy('name')->get() as $child) {
            $this->addFolder($zip, $child, $dirPath);
        }
    }

    private function addFile(ZipArchive $zip, File $file, string $prefix): void
    {
        $source = Storage::disk('local')->path('uploads/'.$file->user_id.'/'.$file->stored_name);
        if (! is_file($source)) {
            $this->missing++;

            return;
        }

        $this->fileCount++;
        $this->totalBytes += (int) filesize($source);
        if ($this->fileCount > self::MAX_FILES) {
            throw new ArchiveLimitException('Terlalu banyak file (maksimal '.self::MAX_FILES.' file per unduhan).');
        }
        if ($this->totalBytes > self::MAX_TOTAL_BYTES) {
            throw new ArchiveLimitException('Ukuran total melebihi 2 GB. Pilih lebih sedikit file.');
        }

        $entry = $this->uniquePath($prefix.$this->sanitize($file->original_name), false);
        $zip->addFile($source, $entry);
        // Already-compressed formats gain nothing from deflate; store them as-is (much faster).
        if (preg_match('/\.(zip|rar|7z|gz|jpe?g|png|gif|webp|mp4|mp3|mkv|mov|avi|pdf|docx|xlsx|pptx)$/i', $entry)) {
            $zip->setCompressionName($entry, ZipArchive::CM_STORE);
        }
    }

    /** Return $path, or "name (2).ext" etc. if it is already taken in the archive. */
    private function uniquePath(string $path, bool $isDir): string
    {
        $key = mb_strtolower($path);
        if (! isset($this->usedPaths[$key])) {
            $this->usedPaths[$key] = true;

            return $path;
        }

        $dir = str_contains($path, '/') ? substr($path, 0, strrpos($path, '/') + 1) : '';
        $base = substr($path, strlen($dir));
        $ext = '';
        if (! $isDir && ($dot = strrpos($base, '.')) > 0) {
            $ext = substr($base, $dot);
            $base = substr($base, 0, $dot);
        }
        for ($i = 2; ; $i++) {
            $candidate = "{$dir}{$base} ({$i}){$ext}";
            if (! isset($this->usedPaths[mb_strtolower($candidate)])) {
                $this->usedPaths[mb_strtolower($candidate)] = true;

                return $candidate;
            }
        }
    }

    private function sanitize(string $name): string
    {
        $name = trim(str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"], '_', $name), ". \t\n\r");

        return $name === '' ? 'tanpa-nama' : $name;
    }

    /** Remove archives left behind by interrupted downloads (older than 1 hour). */
    private function purgeStale(string $dir): void
    {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*.zip') ?: [] as $old) {
            if (@filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }
    }
}

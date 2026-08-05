<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\ActivityService;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index()
    {
        return response()->json(Backup::with('createdBy')->latest()->paginate(20));
    }

    public function store(Request $request, ActivityService $activityService)
    {
        $backup = $this->backupService->create($request->user()->id, 'manual');

        $activityService->log(
            $request->user()->id,
            'created_backup',
            $backup,
            ['filename' => $backup->name, 'status' => $backup->status]
        );

        if ($backup->status === 'failed') {
            return response()->json([
                'message' => 'Backup gagal dibuat',
                'backup' => $backup,
            ], 500);
        }

        return response()->json($backup, 201);
    }

    public function show($id)
    {
        return response()->json(Backup::with('createdBy')->findOrFail($id));
    }

    public function restore(Request $request, $id, ActivityService $activityService)
    {
        $backup = Backup::findOrFail($id);

        try {
            $this->backupService->restore($backup);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal memulihkan backup: ' . $e->getMessage()], 500);
        }

        $activityService->log($request->user()->id, 'restored_backup', $backup, ['filename' => $backup->name]);

        return response()->json(['message' => 'Backup berhasil dipulihkan']);
    }

    public function download($id)
    {
        $backup = Backup::findOrFail($id);

        if ($backup->status !== 'completed') {
            return response()->json(['message' => 'Backup belum selesai dibuat'], 422);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($backup->path, $backup->name);
    }

    public function destroy($id)
    {
        $backup = Backup::findOrFail($id);
        $this->backupService->delete($backup);
        return response()->json(['message' => 'Backup dihapus']);
    }
}

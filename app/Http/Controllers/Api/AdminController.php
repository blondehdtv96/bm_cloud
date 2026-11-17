<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Activity;
use DateTimeZone;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        $storageByRole = Role::with('users:id')->get()
            ->map(function (Role $role) {
                $userIds = $role->users->pluck('id');
                return [
                    'name' => $role->name,
                    'users_count' => $userIds->count(),
                    'storage' => (int) ($userIds->isEmpty() ? 0 : File::whereIn('user_id', $userIds)->sum('size')),
                ];
            })
            ->sortByDesc('storage')
            ->values();

        return response()->json([
            'total_users' => User::count(),
            'total_files' => File::count(),
            'total_folders' => Folder::count(),
            'storage_used' => (int) File::sum('size'),
            'storage_available' => disk_free_space(storage_path('app')),
            'active_users' => User::where('updated_at', '>=', now()->subDays(30))->count(),
            'storage_by_role' => $storageByRole,
        ]);
    }

    /**
     * Filter groups shown in the admin log page, mapped to the raw `action`
     * values written by ActivityService across the app.
     */
    private const ACTION_GROUPS = [
        'upload' => ['uploaded', 'uploaded_new_version'],
        'download' => ['downloaded', 'monitored_file_download'],
        'create' => ['created'],
        'delete' => ['deleted'],
        'share' => ['shared', 'created_share_link'],
        'monitor' => ['monitored_drive', 'monitored_file_preview', 'monitored_file_download'],
        'backup' => ['created_backup', 'restored_backup'],
    ];

    private const ACTION_LABELS = [
        'uploaded' => 'Mengunggah file',
        'uploaded_new_version' => 'Mengunggah versi baru',
        'downloaded' => 'Mengunduh file',
        'created' => 'Membuat folder',
        'deleted' => 'Menghapus item',
        'shared' => 'Membagikan item',
        'created_share_link' => 'Membuat tautan berbagi',
        'monitored_drive' => 'Memantau drive',
        'monitored_file_preview' => 'Pratinjau file (pantau)',
        'monitored_file_download' => 'Mengunduh file (pantau)',
        'created_backup' => 'Membuat backup',
        'restored_backup' => 'Memulihkan backup',
    ];

    public function logs(Request $request)
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 5), 100);

        $paginator = $this->filteredLogsQuery($request)->paginate($perPage);
        $paginator->setCollection($this->withSubjectNames($paginator->getCollection()));

        return response()->json($paginator);
    }

    public function exportLogs(Request $request)
    {
        $filename = 'log-aktivitas-' . now()->format('Ymd-His') . '.csv';
        $query = $this->filteredLogsQuery($request);
        // Match the times shown in the browser (the UI formats in the viewer's timezone).
        $tz = in_array($request->string('tz')->toString(), DateTimeZone::listIdentifiers(), true)
            ? $request->string('tz')->toString()
            : config('app.timezone');

        return response()->streamDownload(function () use ($query, $tz) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 correctly
            fputcsv($out, ['Waktu', 'Pengguna', 'Username', 'Aksi', 'Kode Aksi', 'Item', 'Tipe Item', 'Alamat IP']);

            $query->chunk(500, function ($chunk) use ($out, $tz) {
                foreach ($this->withSubjectNames($chunk) as $log) {
                    fputcsv($out, [
                        $log->created_at?->copy()->timezone($tz)->format('Y-m-d H:i:s'),
                        $log->user?->name ?? '(pengguna dihapus)',
                        $log->user?->username,
                        $log->action_label,
                        $log->action,
                        $log->subject_name,
                        $log->subject_type ? class_basename($log->subject_type) : null,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredLogsQuery(Request $request)
    {
        $query = Activity::with('user:id,name,username,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $action = $request->string('action')->toString();
        if (isset(self::ACTION_GROUPS[$action])) {
            $query->whereIn('action', self::ACTION_GROUPS[$action]);
        } elseif ($action !== '') {
            $query->where('action', $action);
        }

        $days = ['24h' => 1, '7d' => 7, '30d' => 30, '90d' => 90][$request->string('period')->toString()] ?? null;
        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $matchingActions = collect(self::ACTION_LABELS)
                ->filter(fn ($label) => stripos($label, $search) !== false)
                ->keys()->all();

            $query->where(function ($q) use ($like, $matchingActions) {
                $q->where('action', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->withTrashed()
                        ->where(fn ($w) => $w->where('name', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhere('email', 'like', $like)))
                    ->orWhere(fn ($s) => $s->where('subject_type', File::class)
                        ->whereIn('subject_id', File::withTrashed()->where('original_name', 'like', $like)->select('id')))
                    ->orWhere(fn ($s) => $s->where('subject_type', Folder::class)
                        ->whereIn('subject_id', Folder::withTrashed()->where('name', 'like', $like)->select('id')));

                if ($matchingActions) {
                    $q->orWhereIn('action', $matchingActions);
                }
            });
        }

        return $query;
    }

    /**
     * Attach `subject_name` and `action_label` to each log. Subjects are often
     * soft-deleted (files/folders in the trash), so they are loaded withTrashed.
     */
    private function withSubjectNames($logs)
    {
        $names = [];
        foreach ($logs->whereNotNull('subject_type')->groupBy('subject_type') as $type => $group) {
            if (! class_exists($type)) {
                continue;
            }
            $query = $type::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $query->withTrashed();
            }
            foreach ($query->whereIn('id', $group->pluck('subject_id')->unique())->get() as $model) {
                $names[$type][$model->id] = $model->original_name ?? $model->name ?? null;
            }
        }

        return $logs->each(function (Activity $log) use ($names) {
            $log->subject_name = $names[$log->subject_type][$log->subject_id]
                ?? ($log->details['filename'] ?? null);
            $log->action_label = self::ACTION_LABELS[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action));
        });
    }
}
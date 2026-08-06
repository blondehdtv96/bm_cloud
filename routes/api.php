<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\TrashController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DriveMonitorController;
use App\Http\Controllers\Api\DashboardController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/s/{token}', [ShareController::class, 'accessLink']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    Route::apiResource('folders', FolderController::class)->except(['destroy']);
    Route::delete('folders/{id}', [FolderController::class, 'destroy'])->middleware('permission:folders.delete');
    Route::post('folders/{id}/move', [FolderController::class, 'move']);
    
    Route::apiResource('files', FileController::class)->except(['store', 'destroy']);
    Route::delete('files/{id}', [FileController::class, 'destroy'])->middleware('permission:files.delete');
    Route::post('files/upload', [FileController::class, 'upload']);
    Route::get('files/{id}/download', [FileController::class, 'download'])->middleware('permission:files.download');
    Route::get('files/{id}/preview', [FileController::class, 'preview'])->middleware('permission:files.download');
    Route::post('files/{id}/move', [FileController::class, 'move']);
    Route::post('files/{id}/copy', [FileController::class, 'copy']);
    Route::post('files/{id}/versions', [FileController::class, 'uploadNewVersion']);
    Route::get('files/{id}/versions', [FileController::class, 'versions']);
    
    Route::apiResource('shares', ShareController::class)->only(['index', 'destroy']);
    Route::post('shares', [ShareController::class, 'store'])->middleware('permission:shares.create');
    Route::get('shares/recipients', [ShareController::class, 'searchRecipients']);
    Route::get('shares/item/{type}/{id}', [ShareController::class, 'itemShares']);
    Route::put('shares/{id}', [ShareController::class, 'updatePermission']);
    Route::post('shares/link', [ShareController::class, 'createLink'])->middleware('permission:shares.create');
    Route::delete('shares/link/{id}', [ShareController::class, 'destroyLink']);
    
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
    
    Route::get('trash', [TrashController::class, 'index']);
    Route::post('trash/restore/{type}/{id}', [TrashController::class, 'restore']);
    Route::delete('trash/{type}/{id}', [TrashController::class, 'destroy']);
    Route::delete('trash/empty', [TrashController::class, 'empty']);
    
    Route::get('activities', [ActivityController::class, 'index']);
    
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications', [NotificationController::class, 'destroyAll']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    
    Route::get('search', [SearchController::class, 'search']);

    Route::middleware('permission:drive.monitor')->prefix('drive-monitor')->group(function () {
        Route::get('users', [DriveMonitorController::class, 'users']);
        Route::get('users/{userId}/contents', [DriveMonitorController::class, 'contents']);
        Route::get('users/{userId}/folders/{folderId}', [DriveMonitorController::class, 'folderTrail']);
        Route::get('users/{userId}/files/{fileId}/preview', [DriveMonitorController::class, 'previewFile']);
        Route::get('users/{userId}/files/{fileId}/download', [DriveMonitorController::class, 'downloadFile']);
    });

    Route::middleware('role:super_admin,ict')->prefix('admin')->group(function () {
        Route::get('stats', [AdminController::class, 'stats']);
        Route::get('logs', [AdminController::class, 'logs']);
        Route::get('roles', [AdminRoleController::class, 'index']);
        Route::get('roles-list', [AdminRoleController::class, 'simpleList']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('backups', BackupController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('backups/{id}/restore', [BackupController::class, 'restore']);
        Route::get('backups/{id}/download', [BackupController::class, 'download']);
    });
});
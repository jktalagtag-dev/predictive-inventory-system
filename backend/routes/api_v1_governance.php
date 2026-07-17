<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditLogController::class, 'index']);
Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);

Route::get('/settings', [SettingController::class, 'index']);
Route::get('/settings/{settingKey}', [SettingController::class, 'show'])->where('settingKey', '[a-zA-Z0-9_.]+');
Route::put('/settings/{settingKey}', [SettingController::class, 'update'])->where('settingKey', '[a-zA-Z0-9_.]+');

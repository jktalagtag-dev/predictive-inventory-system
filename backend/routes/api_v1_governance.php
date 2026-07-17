<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditLogController::class, 'index']);
Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);

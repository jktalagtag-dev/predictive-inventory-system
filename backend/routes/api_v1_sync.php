<?php

use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::post('/sync/operations', [SyncController::class, 'store']);
Route::get('/sync/operations/{clientOperationId}', [SyncController::class, 'show'])->where('clientOperationId', '[0-9a-fA-F-]{36}');

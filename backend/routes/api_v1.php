<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    require __DIR__.'/api_v1_dashboard.php';
    require __DIR__.'/api_v1_identity.php';
    require __DIR__.'/api_v1_catalog.php';
    require __DIR__.'/api_v1_inventory.php';
    require __DIR__.'/api_v1_procurement.php';
    require __DIR__.'/api_v1_receiving.php';
    require __DIR__.'/api_v1_governance.php';
    require __DIR__.'/api_v1_sales.php';
    require __DIR__.'/api_v1_planning.php';
    require __DIR__.'/api_v1_reporting.php';
    require __DIR__.'/api_v1_sync.php';
});

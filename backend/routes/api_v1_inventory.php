<?php

use App\Http\Controllers\Api\V1\InventoryAdjustmentController;
use App\Http\Controllers\Api\V1\InventoryBalanceController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
use Illuminate\Support\Facades\Route;

Route::get('/inventory/balances', [InventoryBalanceController::class, 'index']);
Route::get('/inventory/movements', [InventoryMovementController::class, 'index']);

Route::get('/inventory/adjustments', [InventoryAdjustmentController::class, 'index']);
Route::post('/inventory/adjustments', [InventoryAdjustmentController::class, 'store']);
Route::get('/inventory/adjustments/{adjustment}', [InventoryAdjustmentController::class, 'show']);
Route::patch('/inventory/adjustments/{adjustment}', [InventoryAdjustmentController::class, 'update']);
Route::post('/inventory/adjustments/{adjustment}/approve', [InventoryAdjustmentController::class, 'approve']);
Route::post('/inventory/adjustments/{adjustment}/post', [InventoryAdjustmentController::class, 'post']);
Route::post('/inventory/adjustments/{adjustment}/reverse', [InventoryAdjustmentController::class, 'reverse']);

<?php

use App\Http\Controllers\Api\V1\ForecastRunController;
use App\Http\Controllers\Api\V1\ReorderPolicyController;
use App\Http\Controllers\Api\V1\RestockingAlertController;
use Illuminate\Support\Facades\Route;

Route::get('/forecast-runs', [ForecastRunController::class, 'index']);
Route::post('/forecast-runs', [ForecastRunController::class, 'store']);
Route::get('/forecast-runs/{forecastRun}', [ForecastRunController::class, 'show']);
Route::get('/forecast-runs/{forecastRun}/items/{productId}', [ForecastRunController::class, 'showItem']);
Route::post('/forecast-runs/{forecastRun}/items/{productId}/manual-plan', [ForecastRunController::class, 'manualPlan']);

Route::get('/reorder-policies', [ReorderPolicyController::class, 'index']);
Route::post('/reorder-policies', [ReorderPolicyController::class, 'store']);
Route::get('/reorder-policies/{reorderPolicy}', [ReorderPolicyController::class, 'show']);
Route::patch('/reorder-policies/{reorderPolicy}', [ReorderPolicyController::class, 'update']);
Route::post('/reorder-policies/{reorderPolicy}/recalculate-rop', [ReorderPolicyController::class, 'recalculateRop']);
Route::post('/reorder-policies/{reorderPolicy}/eoq-calculations', [ReorderPolicyController::class, 'calculateEoq']);
Route::get('/reorder-policies/{reorderPolicy}/eoq-calculations', [ReorderPolicyController::class, 'listEoq']);

Route::post('/restocking-alerts/evaluate', [RestockingAlertController::class, 'evaluate']);
Route::get('/restocking-alerts', [RestockingAlertController::class, 'index']);
Route::get('/restocking-alerts/{restockingAlert}', [RestockingAlertController::class, 'show']);
Route::post('/restocking-alerts/{restockingAlert}/acknowledge', [RestockingAlertController::class, 'acknowledge']);
Route::post('/restocking-alerts/{restockingAlert}/resolve', [RestockingAlertController::class, 'resolve']);
Route::post('/restocking-alerts/{restockingAlert}/dismiss', [RestockingAlertController::class, 'dismiss']);

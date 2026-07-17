<?php

use App\Http\Controllers\Api\V1\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/pos/products', [SaleController::class, 'posProducts']);
Route::get('/sales', [SaleController::class, 'index']);
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/sales/{sale}', [SaleController::class, 'show']);
Route::post('/sales/{sale}/void', [SaleController::class, 'void']);
Route::post('/sales/{sale}/refunds', [SaleController::class, 'refund']);

<?php

use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\SupplierContactController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);

Route::post('/suppliers/{supplier}/contacts', [SupplierContactController::class, 'store']);
Route::patch('/suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'update']);

Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
Route::patch('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit']);
Route::post('/purchase-orders/{purchaseOrder}/approvals', [PurchaseOrderController::class, 'decide']);
Route::post('/purchase-orders/{purchaseOrder}/mark-ordered', [PurchaseOrderController::class, 'markOrdered']);
Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
Route::post('/purchase-orders/{purchaseOrder}/close', [PurchaseOrderController::class, 'close']);

<?php

use App\Http\Controllers\Api\V1\GoodsReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/goods-receipts', [GoodsReceiptController::class, 'index']);
Route::post('/goods-receipts', [GoodsReceiptController::class, 'store']);
Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show']);
Route::patch('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'update']);
Route::post('/goods-receipts/{goodsReceipt}/post', [GoodsReceiptController::class, 'post']);
Route::post('/goods-receipts/{goodsReceipt}/reverse', [GoodsReceiptController::class, 'reverse']);

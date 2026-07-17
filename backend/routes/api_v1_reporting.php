<?php

use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/reports', [ReportController::class, 'index']);
Route::get('/reports/{reportCode}', [ReportController::class, 'show'])->where('reportCode', '[a-z0-9-]+');

Route::post('/report-exports', [ReportExportController::class, 'store']);
Route::get('/report-exports/{reportExport}', [ReportExportController::class, 'show']);
Route::get('/report-exports/{reportExport}/download', [ReportExportController::class, 'download']);

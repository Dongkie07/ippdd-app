<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WfpImportController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

// ── Dashboard ─────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index']);

// ── Budget breakdown ────────────────────────────────────
Route::get('/budget', [BudgetController::class, 'index']);

// ── Manual entry (CRUD) ──────────────────────────────────────
// IMPORTANT: specific routes (/year) must come BEFORE wildcard /{id}
Route::get('/departments',                  [DepartmentController::class, 'index']);
Route::post('/departments/year',            [DepartmentController::class, 'storeYear']);
Route::delete('/departments/year/{year}',   [DepartmentController::class, 'destroyYear']);
Route::post('/departments',                 [DepartmentController::class, 'store']);
Route::put('/departments/{id}',             [DepartmentController::class, 'update']);
Route::delete('/departments/{id}',          [DepartmentController::class, 'destroy']);


// ── Office registry / rename history ───────────────────────────────
Route::get('/offices', [OfficeController::class, 'index']);
Route::post('/offices/sync', [OfficeController::class, 'sync']);
Route::post('/offices', [OfficeController::class, 'store']);
Route::put('/offices/{office}', [OfficeController::class, 'update']);
Route::post('/offices/{office}/histories', [OfficeController::class, 'storeHistory']);
Route::delete('/office-histories/{history}', [OfficeController::class, 'destroyHistory']);

// ── Upload ────────────────────────────────────────────────────
Route::get('/upload',          [WfpImportController::class, 'index']);
Route::post('/upload/parse',   [WfpImportController::class, 'parse']);
Route::post('/upload/confirm', [WfpImportController::class, 'confirm']);

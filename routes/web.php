<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WfpImportController;
use App\Http\Controllers\AiInsightsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

// ── Upload ────────────────────────────────────────────────────
Route::get('/upload',          [WfpImportController::class, 'index']);
Route::post('/upload/parse',   [WfpImportController::class, 'parse']);
Route::post('/upload/confirm', [WfpImportController::class, 'confirm']);

// ── AI Insights (streaming) ───────────────────────────────────
Route::get('/ai/analyze', [AiInsightsController::class, 'analyze']);

// ── Export ────────────────────────────────────────────────────
Route::get('/export/excel', [ExportController::class, 'excel']);
Route::get('/export/csv',   [ExportController::class, 'csv']);

// ── Stub pages ────────────────────────────────────────────────
Route::get('/reports',  fn() => Inertia::render('Reports'));
Route::get('/users',    fn() => Inertia::render('Users'));
Route::get('/settings', fn() => Inertia::render('Settings'));
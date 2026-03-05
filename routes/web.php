<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WfpImportController;
use App\Http\Controllers\AiInsightsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Dashboard ─────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index']);

// ── Budget breakdown ────────────────────────────────────
Route::get('/budget', [BudgetController::class, 'index']);

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
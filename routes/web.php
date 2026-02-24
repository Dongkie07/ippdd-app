<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ── Dashboard ─────────────────────────────────────────────────
Route::get('/',           [DashboardController::class, 'index']);
Route::get('/budget',     [DashboardController::class, 'budget']);
Route::get('/indicators', [DashboardController::class, 'indicators']);
Route::get('/upload',     [DashboardController::class, 'upload']);
Route::get('/reports',    [DashboardController::class, 'reports']);
Route::get('/users',      [DashboardController::class, 'users']);
Route::get('/settings',   [DashboardController::class, 'settings']);

// ── Future: Upload POST endpoint ──────────────────────────────
Route::post('/upload', [DashboardController::class, 'processUpload']);

// ── Future: Auth routes ───────────────────────────────────────
// Route::middleware('auth')->group(function () { ... });
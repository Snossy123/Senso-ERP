<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\TenantApiController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'tenant'])->prefix('dashboard')->group(function () {
    Route::get('/widgets/{widget}', [DashboardController::class, 'widget']);
    Route::post('/widgets/refresh', [DashboardController::class, 'refresh']);
});

Route::middleware(['auth:sanctum', 'tenant'])->prefix('tenants')->group(function () {
    Route::get('/', [TenantApiController::class, 'index']);
    Route::get('/{tenant}', [TenantApiController::class, 'show']);
    Route::get('/{tenant}/usage', [TenantApiController::class, 'usage']);
    Route::post('/{tenant}/upgrade-plan', [TenantApiController::class, 'upgradePlan']);
    Route::post('/{tenant}/suspend', [TenantApiController::class, 'suspend']);
    Route::post('/{tenant}/activate', [TenantApiController::class, 'activate']);
    Route::post('/{tenant}/sync-usage', [TenantApiController::class, 'syncUsage']);
    Route::patch('/{tenant}/settings', [TenantApiController::class, 'updateSettings']);
});

Route::middleware('auth:sanctum')->prefix('plans')->group(function () {
    Route::get('/', [TenantApiController::class, 'plans']);
});

Route::middleware('auth:sanctum')->prefix('usage')->group(function () {
    Route::post('/check', [TenantApiController::class, 'checkLimits']);
});

Route::middleware(['auth:sanctum', 'tenant'])->prefix('accounting')->group(function () {
    // Chart of Accounts
    Route::get('/accounts', [\App\Http\Controllers\Accounting\AccountController::class, 'index']);
    Route::post('/accounts', [\App\Http\Controllers\Accounting\AccountController::class, 'store']);

    // Journal Entries
    Route::get('/journal-entries', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'index']);
    Route::post('/journal-entries', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'store']);
    Route::get('/journal-entries/{id}', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'show']);

    // Reports
    Route::get('/reports/trial-balance', [\App\Http\Controllers\Accounting\ReportController::class, 'trialBalance']);
    Route::get('/reports/income-statement', [\App\Http\Controllers\Accounting\ReportController::class, 'incomeStatement']);
    Route::get('/reports/balance-sheet', [\App\Http\Controllers\Accounting\ReportController::class, 'balanceSheet']);
    Route::get('/reports/general-ledger', [\App\Http\Controllers\Accounting\ReportController::class, 'generalLedger']);
});

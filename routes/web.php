<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\DebtController;
use App\Http\Controllers\Web\BudgetController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// MyFinance Application Routes
Route::middleware(['web'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
    });

    // Accounts
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
    });

    // Debts & Installments
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])->name('index');
    });

    // Budgets
    Route::prefix('budgets')->name('budgets.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
    });

});

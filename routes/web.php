<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\DebtController;
use App\Http\Controllers\Web\BudgetController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\RoleManagementController;
use App\Http\Controllers\Web\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Register
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Forgot Password
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');

    // Reset Password
    Route::get('/reset-password/{token}',  [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',         [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// MyFinance Application Routes (Protected by Auth)
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transactions
    Route::prefix('transactions')->name('transactions.')->middleware('menu.permission:transactions')->group(function () {
        Route::get('/',           [TransactionController::class, 'index'])->name('index');
        Route::get('/create',     [TransactionController::class, 'create'])->name('create');
        Route::post('/',          [TransactionController::class, 'store'])->name('store');
        Route::get('/{id}/edit',  [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [TransactionController::class, 'update'])->name('update');
        Route::delete('/{id}',    [TransactionController::class, 'destroy'])->name('destroy');
    });

    // Accounts
    Route::prefix('accounts')->name('accounts.')->middleware('menu.permission:accounts')->group(function () {
        Route::get('/',           [AccountController::class, 'index'])->name('index');
        Route::get('/create',     [AccountController::class, 'create'])->name('create');
        Route::post('/',          [AccountController::class, 'store'])->name('store');
        Route::get('/{id}',       [AccountController::class, 'show'])->name('show');
        Route::get('/{id}/edit',  [AccountController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [AccountController::class, 'update'])->name('update');
        Route::delete('/{id}',    [AccountController::class, 'destroy'])->name('destroy');
    });

    // Debts & Installments
    Route::prefix('debts')->name('debts.')->middleware('menu.permission:debts')->group(function () {
        Route::get('/',                            [DebtController::class, 'index'])->name('index');
        Route::get('/create',                      [DebtController::class, 'create'])->name('create');
        Route::post('/',                           [DebtController::class, 'store'])->name('store');
        Route::get('/{id}',                        [DebtController::class, 'show'])->name('show');
        Route::get('/{id}/edit',                   [DebtController::class, 'edit'])->name('edit');
        Route::put('/{id}',                        [DebtController::class, 'update'])->name('update');
        Route::delete('/{id}',                     [DebtController::class, 'destroy'])->name('destroy');
        // Installment payment
        Route::post('/{debtId}/pay/{installmentId}', [DebtController::class, 'payInstallment'])->name('pay-installment');
    });

    // Budgets
    Route::prefix('budgets')->name('budgets.')->middleware('menu.permission:budgets')->group(function () {
        Route::get('/',           [BudgetController::class, 'index'])->name('index');
        Route::get('/create',     [BudgetController::class, 'create'])->name('create');
        Route::post('/',          [BudgetController::class, 'store'])->name('store');
        Route::get('/{id}/edit',  [BudgetController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [BudgetController::class, 'update'])->name('update');
        Route::delete('/{id}',    [BudgetController::class, 'destroy'])->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('menu.permission:reports')->group(function () {
        Route::get('/',           [ReportController::class, 'index'])->name('index');
        Route::get('/cashflow',   [ReportController::class, 'cashflow'])->name('cashflow');
        Route::get('/export',     [ReportController::class, 'export'])->name('export');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->middleware('menu.permission:settings')->group(function () {
        Route::get('/',           [SettingController::class, 'index'])->name('index');
        Route::put('/profile',    [SettingController::class, 'updateProfile'])->name('update-profile');
        Route::put('/password',   [SettingController::class, 'updatePassword'])->name('update-password');
        Route::get('/categories', [SettingController::class, 'categories'])->name('categories');
        Route::post('/categories', [SettingController::class, 'storeCategory'])->name('store-category');
        Route::put('/categories/{id}', [SettingController::class, 'updateCategory'])->name('update-category');
        Route::delete('/categories/{id}', [SettingController::class, 'destroyCategory'])->name('destroy-category');
    });

    // Role & Access Management (RBAC)
    Route::prefix('roles')->name('roles.')->middleware('menu.permission:roles')->group(function () {
        Route::get('/',                 [RoleManagementController::class, 'index'])->name('index');
        Route::post('/',                [RoleManagementController::class, 'storeRole'])->name('store');
        Route::put('/{id}/permissions', [RoleManagementController::class, 'updatePermissions'])->name('update-permissions');
        Route::post('/assign-user',     [RoleManagementController::class, 'assignUserRole'])->name('assign-user');
        Route::delete('/{id}',          [RoleManagementController::class, 'destroyRole'])->name('destroy');
    });

    // Realtime Notifications & Polling
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/poll',             [NotificationController::class, 'poll'])->name('poll');
        Route::post('/{id}/read',       [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all',        [NotificationController::class, 'markAllAsRead'])->name('read-all');
    });

});

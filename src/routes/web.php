<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Visitantes (não autenticados) ────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Redefinição de senha via link enviado por e-mail
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Área autenticada (verifica status ativo em TODA rota) ────────────────
Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Logs de auditoria (Admin e Auditor)
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/logs', [AuditLogController::class, 'index'])->name('index');
        Route::get('/logs/export/csv', [AuditLogController::class, 'exportCsv'])->name('export.csv');
        Route::post('/logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('export.pdf');
    });

    // Meu Perfil (Admin e Auditor)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // Administração de Usuários (somente Admin)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/users/{user}/reset-link', [UserController::class, 'sendResetLink'])->name('users.reset-link');

        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});

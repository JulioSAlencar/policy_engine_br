<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ExtensionAuthController;
use App\Http\Controllers\Api\PasswordResetController;

// ─── Autenticação genérica (API pública) ──────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/reset-password',  [PasswordResetController::class, 'reset'])->name('password.update');

// ─── Autenticação da Extensão do Chrome ───────────────────────────────────
Route::prefix('extension')->name('extension.')->group(function () {
    Route::post('/login', [ExtensionAuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')
        ->post('/logout', [ExtensionAuthController::class, 'logout'])
        ->name('logout');
});

// ─── Rotas protegidas por Sanctum ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',    [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/audit/logs', [AuditController::class, 'store']);
});

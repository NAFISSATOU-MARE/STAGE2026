<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DirectionController;
use App\Http\Controllers\Api\DemandeController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authentifié ──────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::get('/solde',            [AuthController::class, 'solde']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Référentiel
    Route::get('/directions',                       [DirectionController::class, 'index']);
    Route::get('/directions/{direction}/divisions', [DirectionController::class, 'divisions']);

    // Demandes (tout agent sauf ADMIN)
    Route::get('/demandes',                      [DemandeController::class, 'index']);
    Route::post('/demandes',                     [DemandeController::class, 'store']);
    Route::get('/demandes/{demande}',            [DemandeController::class, 'show']);
    Route::get('/demandes/{demande}/pdf',        [DemandeController::class, 'pdf']);
    Route::put('/demandes/{demande}/lettre',     [DemandeController::class, 'updateLettre']);

    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::put('/notifications/lire-tout',    [NotificationController::class, 'lireTout']);
    Route::put('/notifications/{notification}/lire', [NotificationController::class, 'lire']);

    // Validations (réservé aux valideurs)
    Route::middleware('role:CHEF_DIVISION,DIRECTEUR,DAP,DRH,DGB,MINISTRE')->group(function () {
        Route::get('/validations/pending',    [ValidationController::class, 'pending']);
        Route::get('/validations/history',    [ValidationController::class, 'history']);
        Route::post('/validations/{demande}', [ValidationController::class, 'store']);
    });

    // Administration (ADMIN global ou ADMIN par direction)
    Route::middleware('role:ADMIN,ADMIN_DIRECTION')->prefix('admin')->group(function () {
        Route::get('/dashboard',          [AdminController::class, 'dashboard']);
        Route::get('/demandes',           [AdminController::class, 'indexDemandes']);
        Route::get('/agents',             [AdminController::class, 'indexAgents']);
        Route::post('/agents',            [AdminController::class, 'storeAgent']);
        Route::put('/agents/{agent}',     [AdminController::class, 'updateAgent']);
        Route::delete('/agents/{agent}',  [AdminController::class, 'destroyAgent']);
    });
});

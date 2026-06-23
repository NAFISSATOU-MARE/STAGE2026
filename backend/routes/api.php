<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DirectionController;
use App\Http\Controllers\Api\DemandeController;
use App\Http\Controllers\Api\ValidationController;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authentifié ──────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/me',       [AuthController::class, 'me']);
    Route::get('/solde',    [AuthController::class, 'solde']);

    // Référentiel
    Route::get('/directions',                         [DirectionController::class, 'index']);
    Route::get('/directions/{direction}/divisions',   [DirectionController::class, 'divisions']);

    // Demandes (tout agent authentifié)
    Route::get('/demandes',          [DemandeController::class, 'index']);
    Route::post('/demandes',         [DemandeController::class, 'store']);
    Route::get('/demandes/{demande}',[DemandeController::class, 'show']);
    Route::get('/demandes/{demande}/pdf', [DemandeController::class, 'pdf']);

    // Validations (réservé aux valideurs)
    Route::middleware('role:CHEF_DIVISION,DIRECTEUR,DAP,DRH')->group(function () {
        Route::get('/validations/pending',       [ValidationController::class, 'pending']);
        Route::post('/validations/{demande}',    [ValidationController::class, 'store']);
    });
});

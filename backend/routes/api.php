<?php

use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login',  [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/me',     [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::get('/solde',  [\App\Http\Controllers\Api\AuthController::class, 'solde']);

    Route::get('/directions',                    [\App\Http\Controllers\Api\DirectionController::class, 'index']);
    Route::get('/directions/{direction}/divisions', [\App\Http\Controllers\Api\DirectionController::class, 'divisions']);

    Route::apiResource('/demandes', \App\Http\Controllers\Api\DemandeController::class)
         ->only(['index', 'store', 'show']);
    Route::get('/demandes/{demande}/pdf', [\App\Http\Controllers\Api\DemandeController::class, 'pdf']);

    Route::get('/validations/pending',         [\App\Http\Controllers\Api\ValidationController::class, 'pending']);
    Route::post('/validations/{demande}',      [\App\Http\Controllers\Api\ValidationController::class, 'store']);
});

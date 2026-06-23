<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemandeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5']);
    }

    public function store(Request $request): JsonResponse
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5'], 501);
    }

    public function show(Demande $demande): JsonResponse
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5']);
    }

    public function pdf(Demande $demande)
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5']);
    }
}

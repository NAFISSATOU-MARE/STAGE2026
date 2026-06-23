<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5']);
    }

    public function store(Request $request, Demande $demande): JsonResponse
    {
        // Étape 5
        return response()->json(['message' => 'À implémenter — étape 5'], 501);
    }
}

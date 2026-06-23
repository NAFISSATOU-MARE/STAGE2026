<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use Illuminate\Http\JsonResponse;

class DirectionController extends Controller
{
    public function index(): JsonResponse
    {
        $directions = Direction::with('divisions:id,direction_id,sigle,nom')
                               ->orderBy('sigle')
                               ->get(['id', 'sigle', 'nom']);

        return response()->json($directions);
    }

    public function divisions(Direction $direction): JsonResponse
    {
        $divisions = $direction->divisions()
                               ->orderBy('sigle')
                               ->get(['id', 'direction_id', 'sigle', 'nom']);

        return response()->json($divisions);
    }
}

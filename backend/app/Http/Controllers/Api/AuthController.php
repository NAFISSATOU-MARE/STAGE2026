<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $agent = Agent::with(['direction', 'division'])
                      ->where('email', $request->email)
                      ->first();

        if (! $agent || ! Hash::check($request->password, $agent->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        // Une seule session active à la fois
        $agent->tokens()->delete();

        $token = $agent->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'agent' => $this->format($agent),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        $agent = $request->user()->load(['direction', 'division']);
        return response()->json($this->format($agent));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'new_password'              => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        $agent = $request->user();

        $agent->update([
            'password'             => Hash::make($data['new_password']),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    public function solde(Request $request): JsonResponse
    {
        $agent          = $request->user();
        $decisionActive = $agent->decisionActive();

        return response()->json([
            'solde_disponible'     => $agent->soldeJours(),
            'peut_soumettre_conge' => $agent->peutSoumettreConge(),
            'decision_active'      => $decisionActive ? [
                'id'               => $decisionActive->id,
                'date_validation'  => $decisionActive->date_validation->toDateString(),
                'date_expiration'  => $decisionActive->date_validation
                                        ->copy()
                                        ->addDays($decisionActive->duree_jours ?? 180)
                                        ->toDateString(),
                'duree_jours'      => $decisionActive->duree_jours,
                'numero_reference' => $decisionActive->numero_reference,
            ] : null,
        ]);
    }

    private function format(Agent $agent): array
    {
        return [
            'id'                   => $agent->id,
            'nom'                  => $agent->nom,
            'prenom'               => $agent->prenom,
            'nom_complet'          => $agent->nom_complet,
            'email'                => $agent->email,
            'poste'                => $agent->poste,
            'corps'                => $agent->corps,
            'profil'               => $agent->profil,
            'matricule'            => $agent->matricule,
            'telephone'            => $agent->telephone,
            'role'                 => $agent->role,
            'must_change_password' => (bool) $agent->must_change_password,
            'direction'            => $agent->direction?->only(['id', 'sigle', 'nom']),
            'division'             => $agent->division?->only(['id', 'sigle', 'nom']),
            'solde_disponible'     => $agent->soldeJours(),
            'peut_soumettre_conge' => $agent->peutSoumettreConge(),
        ];
    }
}

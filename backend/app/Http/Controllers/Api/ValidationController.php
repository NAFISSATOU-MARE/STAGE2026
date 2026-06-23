<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Demande;
use App\Models\Validation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    // ─── GET /api/validations/pending ─────────────────────────────────────────
    public function pending(Request $request): JsonResponse
    {
        $valideur = $request->user();

        $query = Demande::with([
            'agent:id,nom,prenom,profil,matricule,corps,poste,direction_id,division_id',
            'agent.direction:id,sigle,nom',
            'agent.division:id,sigle,nom',
            'validations.valideur:id,nom,prenom,role',
        ])->where('statut', 'EN_ATTENTE');

        match ($valideur->role) {
            'CHEF_DIVISION' => $query
                ->where('niveau_courant', 1)
                ->whereHas('agent', fn($q) => $q->where('division_id', $valideur->division_id)),

            'DIRECTEUR'     => $query
                ->where('niveau_courant', 2)
                ->whereHas('agent', fn($q) => $q->where('direction_id', $valideur->direction_id)),

            'DAP'           => $query
                ->where('niveau_courant', 3)
                ->where('type', 'DECISION'),

            'DRH'           => $query
                ->where('niveau_courant', 4)
                ->where('type', 'DECISION'),

            default         => $query->whereRaw('1 = 0'),
        };

        return response()->json($query->orderBy('created_at')->get());
    }

    // ─── POST /api/validations/{demande} ──────────────────────────────────────
    public function store(Request $request, Demande $demande): JsonResponse
    {
        $valideur = $request->user();

        // Vérification que ce valideur est bien habilité à traiter cette demande
        $this->verifierHabilitation($valideur, $demande);

        $data = $request->validate([
            'avis'        => 'required|in:FAVORABLE,DEFAVORABLE',
            'motif_refus' => 'required_if:avis,DEFAVORABLE|nullable|string|max:1000',
        ]);

        // Enregistrement de l'avis
        Validation::create([
            'demande_id'  => $demande->id,
            'valideur_id' => $valideur->id,
            'niveau'      => $demande->niveau_courant,
            'avis'        => $data['avis'],
            'motif_refus' => $data['motif_refus'] ?? null,
        ]);

        if ($data['avis'] === 'DEFAVORABLE') {
            // Circuit arrêté : demande rejetée
            $demande->update(['statut' => 'REJETEE']);
        } else {
            if ($demande->niveau_courant >= $demande->niveauxMax()) {
                // Dernier niveau favorable → approbation finale
                $demande->load('agent.direction');
                $reference = Demande::genererReference($demande->agent);
                $demande->update([
                    'statut'           => 'APPROUVEE',
                    'numero_reference' => $reference,
                ]);
            } else {
                // Passage au niveau suivant
                $demande->increment('niveau_courant');
            }
        }

        return response()->json(
            $demande->fresh(['agent.direction', 'agent.division', 'validations.valideur'])
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function verifierHabilitation(Agent $valideur, Demande $demande): void
    {
        if ($demande->statut !== 'EN_ATTENTE') {
            abort(422, 'Cette demande est déjà traitée (statut : ' . $demande->statut . ').');
        }

        $demande->load('agent');

        $habilite = match ($valideur->role) {
            'CHEF_DIVISION' => $demande->niveau_courant === 1
                               && $demande->agent->division_id === $valideur->division_id,

            'DIRECTEUR'     => $demande->niveau_courant === 2
                               && $demande->agent->direction_id === $valideur->direction_id,

            'DAP'           => $demande->niveau_courant === 3
                               && $demande->type === 'DECISION',

            'DRH'           => $demande->niveau_courant === 4
                               && $demande->type === 'DECISION',

            default         => false,
        };

        if (! $habilite) {
            abort(403, "Vous n'êtes pas habilité à valider cette demande à ce niveau.");
        }
    }
}

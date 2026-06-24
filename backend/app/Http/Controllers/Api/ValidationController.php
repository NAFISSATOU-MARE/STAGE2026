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
            'agent:id,nom,prenom,profil,matricule,corps,poste,direction_id,division_id,role',
            'agent.direction:id,sigle,nom',
            'agent.division:id,sigle,nom',
            'validations.valideur:id,nom,prenom,role',
        ])->where('statut', 'EN_ATTENTE');

        match ($valideur->role) {

            // Valide les demandes AGENT en niveau 1 (sa division uniquement)
            'CHEF_DIVISION' => $query
                ->where('niveau_courant', 1)
                ->whereHas('agent', fn($q) => $q
                    ->where('role', 'AGENT')
                    ->where('division_id', $valideur->division_id)
                ),

            // Valide :
            //  - AGENT niveau 2 (sa direction)
            //  - CHEF_DIVISION niveau 1, tout type (sa direction)
            'DIRECTEUR' => $query->where(function ($q) use ($valideur) {
                $q->where(function ($q2) use ($valideur) {
                    $q2->where('niveau_courant', 2)
                       ->whereHas('agent', fn($a) => $a
                           ->where('role', 'AGENT')
                           ->where('direction_id', $valideur->direction_id)
                       );
                })->orWhere(function ($q2) use ($valideur) {
                    $q2->where('niveau_courant', 1)
                       ->whereHas('agent', fn($a) => $a
                           ->where('role', 'CHEF_DIVISION')
                           ->where('direction_id', $valideur->direction_id)
                       );
                });
            }),

            // Valide DECISION :
            //  - AGENT niveau 3
            //  - CHEF_DIVISION niveau 2
            'DAP' => $query->where('type', 'DECISION')->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('niveau_courant', 3)
                       ->whereHas('agent', fn($a) => $a->where('role', 'AGENT'));
                })->orWhere(function ($q2) {
                    $q2->where('niveau_courant', 2)
                       ->whereHas('agent', fn($a) => $a->where('role', 'CHEF_DIVISION'));
                });
            }),

            // Valide DECISION :
            //  - AGENT niveau 4
            //  - CHEF_DIVISION niveau 3
            //  - DIRECTEUR niveau 2
            'DRH' => $query->where('type', 'DECISION')->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('niveau_courant', 4)
                       ->whereHas('agent', fn($a) => $a->where('role', 'AGENT'));
                })->orWhere(function ($q2) {
                    $q2->where('niveau_courant', 3)
                       ->whereHas('agent', fn($a) => $a->where('role', 'CHEF_DIVISION'));
                })->orWhere(function ($q2) {
                    $q2->where('niveau_courant', 2)
                       ->whereHas('agent', fn($a) => $a->where('role', 'DIRECTEUR'));
                });
            }),

            // Valide les demandes DIRECTEUR en niveau 1 (tout type)
            'DGB' => $query
                ->where('niveau_courant', 1)
                ->whereHas('agent', fn($q) => $q->where('role', 'DIRECTEUR')),

            // Valide les demandes DGB en niveau 1 (tout type)
            'MINISTRE' => $query
                ->where('niveau_courant', 1)
                ->whereHas('agent', fn($q) => $q->where('role', 'DGB')),

            default => $query->whereRaw('1 = 0'),
        };

        return response()->json($query->orderBy('created_at')->get());
    }

    // ─── POST /api/validations/{demande} ──────────────────────────────────────
    public function store(Request $request, Demande $demande): JsonResponse
    {
        $valideur = $request->user();
        $demande->load('agent');

        $this->verifierHabilitation($valideur, $demande);

        // Détermine si cette validation est la dernière étape d'une DECISION
        $isFinalDecision = $demande->type === 'DECISION'
            && $demande->niveau_courant >= $demande->niveauxMax();

        $data = $request->validate([
            'avis'        => 'required|in:FAVORABLE,DEFAVORABLE',
            'motif_refus' => 'required_if:avis,DEFAVORABLE|nullable|string|max:1000',
            'duree_jours' => [
                'nullable', 'integer', 'between:1,90',
                function (string $attr, mixed $value, \Closure $fail) use ($isFinalDecision, $request) {
                    if ($isFinalDecision
                        && $request->input('avis') === 'FAVORABLE'
                        && empty($value)
                    ) {
                        $fail('La durée de validité (1–90 jours) est obligatoire pour approuver une décision.');
                    }
                },
            ],
        ]);

        Validation::create([
            'demande_id'  => $demande->id,
            'valideur_id' => $valideur->id,
            'niveau'      => $demande->niveau_courant,
            'avis'        => $data['avis'],
            'motif_refus' => $data['motif_refus'] ?? null,
        ]);

        if ($data['avis'] === 'DEFAVORABLE') {
            $demande->update(['statut' => 'REJETEE']);
        } else {
            if ($demande->niveau_courant >= $demande->niveauxMax()) {
                $demande->load('agent.direction');
                $reference  = Demande::genererReference($demande->agent);
                $updateData = [
                    'statut'           => 'APPROUVEE',
                    'numero_reference' => $reference,
                    'date_validation'  => now(),
                ];
                if ($demande->type === 'DECISION') {
                    $updateData['duree_jours'] = (int) $data['duree_jours'];
                }
                $demande->update($updateData);
            } else {
                $demande->increment('niveau_courant');
            }
        }

        return response()->json(
            $demande->fresh(['agent.direction', 'agent.division', 'validations.valideur'])
        );
    }

    // ─── GET /api/validations/history ────────────────────────────────────────
    public function history(Request $request): JsonResponse
    {
        $valideur = $request->user();

        $validations = Validation::with([
            'demande:id,type,statut,nombre_jours,date_debut,date_fin,motif,agent_id',
            'demande.agent:id,nom,prenom,matricule',
        ])
        ->where('valideur_id', $valideur->id)
        ->orderByDesc('created_at')
        ->get();

        return response()->json($validations);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function verifierHabilitation(Agent $valideur, Demande $demande): void
    {
        if ($demande->statut !== 'EN_ATTENTE') {
            abort(422, 'Cette demande est déjà traitée (statut : ' . $demande->statut . ').');
        }

        $circuit    = $demande->circuit();
        $roleRequis = $circuit[$demande->niveau_courant - 1] ?? null;

        if ($valideur->role !== $roleRequis) {
            abort(403, "Vous n'êtes pas habilité à valider cette demande à ce niveau.");
        }

        // Vérification de la juridiction géographique (CHEF_DIVISION et DIRECTEUR seulement)
        $habilite = match ($valideur->role) {
            'CHEF_DIVISION' => $demande->agent->division_id === $valideur->division_id,
            'DIRECTEUR'     => $demande->agent->direction_id === $valideur->direction_id,
            default         => true, // DAP, DRH, DGB, MINISTRE : autorité centrale
        };

        if (! $habilite) {
            abort(403, "Vous n'êtes pas habilité à valider cette demande à ce niveau.");
        }
    }
}

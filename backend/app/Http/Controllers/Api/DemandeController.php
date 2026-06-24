<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DemandeController extends Controller
{
    // ─── GET /api/demandes ────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->demandes()
            ->with(['validations.valideur:id,nom,prenom,role'])
            ->orderByDesc('created_at');

        if ($request->filled('annee')) {
            $query->where('annee', $request->integer('annee'));
        }

        return response()->json($query->get());
    }

    // ─── POST /api/demandes ───────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $agent = $request->user();

        $data = $request->validate([
            'type'            => 'required|in:CONGE,DECISION',
            'date_debut'      => 'required_if:type,CONGE|nullable|date|after_or_equal:today',
            'date_fin'        => 'required_if:type,CONGE|nullable|date|after_or_equal:date_debut',
            'motif'           => 'required|string|max:1000',
            'lieu_jouissance' => 'nullable|string|max:255',
        ]);

        // Vérification du type selon le profil
        if ($data['type'] === 'DECISION' && $agent->profil !== 'AGENT_ETAT') {
            return response()->json([
                'message' => "Seuls les agents de l'État peuvent soumettre une demande de décision.",
            ], 422);
        }

        // Tous les AGENT_ETAT sauf DGB et MINISTRE nécessitent une décision active pour un congé.
        $decisionActive = null;
        if ($data['type'] === 'CONGE'
            && $agent->profil === 'AGENT_ETAT'
            && ! in_array($agent->role, ['DGB', 'MINISTRE'], true)
        ) {
            $decisionActive = $agent->decisionActive();
            if (! $decisionActive) {
                return response()->json([
                    'message' => 'Vous devez avoir une décision active pour soumettre une demande de congé.',
                ], 422);
            }
        }

        // Interdiction d'avoir deux demandes EN_ATTENTE simultanément
        if ($agent->demandes()->where('statut', 'EN_ATTENTE')->exists()) {
            return response()->json([
                'message' => 'Vous avez déjà une demande en cours de validation.',
            ], 422);
        }

        if ($data['type'] === 'DECISION') {
            $nombreJours = 0;
        } else {
            $nombreJours = Carbon::parse($data['date_debut'])->diffInDays($data['date_fin']) + 1;
            $solde = $agent->soldeJours();
            if ($nombreJours > $solde) {
                return response()->json([
                    'message' => "Solde insuffisant. Vous disposez de {$solde} jour(s) disponible(s).",
                ], 422);
            }
        }

        $demande = Demande::create([
            'agent_id'              => $agent->id,
            'type'                  => $data['type'],
            'date_debut'            => $data['date_debut'] ?? now()->toDateString(),
            'date_fin'              => $data['date_fin']   ?? now()->toDateString(),
            'nombre_jours'          => $nombreJours,
            'motif'                 => $data['motif'],
            'lieu_jouissance'       => $data['lieu_jouissance'] ?? null,
            'statut'                => 'EN_ATTENTE',
            'niveau_courant'        => 1,
            'annee'                 => now()->year,
            'decision_reference_id' => $decisionActive?->id,
        ]);

        return response()->json(
            $demande->load(['agent.direction', 'agent.division', 'validations']),
            201
        );
    }

    // ─── GET /api/demandes/{demande} ──────────────────────────────────────────
    public function show(Request $request, Demande $demande): JsonResponse
    {
        $this->autoriserAcces($request->user(), $demande);

        return response()->json(
            $demande->load(['agent.direction', 'agent.division', 'validations.valideur'])
        );
    }

    // ─── GET /api/demandes/{demande}/pdf ──────────────────────────────────────
    public function pdf(Request $request, Demande $demande): Response
    {
        $this->autoriserAcces($request->user(), $demande);

        $demande->load(['agent.direction', 'agent.division', 'validations.valideur']);
        $agent = $demande->agent;

        // Dernière demande approuvée avec référence officielle (pour remplir le champ du formulaire)
        $derniereDemande = Demande::where('agent_id', $agent->id)
            ->where('statut', 'APPROUVEE')
            ->whereNotNull('numero_reference')
            ->where('id', '<', $demande->id)
            ->latest()
            ->first();

        // Jours accordés/déductibles depuis cette dernière décision
        $joursDepuisDerniere = $derniereDemande
            ? (int) Demande::where('agent_id', $agent->id)
                ->where('statut', 'APPROUVEE')
                ->where('id', '>', $derniereDemande->id)
                ->sum('nombre_jours')
            : 0;

        $pdf = Pdf::loadView('pdf.demande-conge', [
            'demande'             => $demande,
            'agent'               => $agent,
            'derniereDemande'     => $derniereDemande,
            'joursDepuisDerniere' => $joursDepuisDerniere,
            'dateReprise'         => Carbon::parse($demande->date_fin)->addDay()->translatedFormat('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        $ref      = $demande->numero_reference ? str_replace('/', '-', $demande->numero_reference) : 'DGB-' . $demande->id;
        $filename = "demande-{$ref}.pdf";

        return $pdf->download($filename);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function autoriserAcces($user, Demande $demande): void
    {
        $estProprietaire = $demande->agent_id === $user->id;
        $estValideur     = \in_array($user->role, ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE'], true);

        if (! $estProprietaire && ! $estValideur) {
            abort(403, 'Accès non autorisé à cette demande.');
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Demande;
use App\Models\Notification;
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

        // Le Ministre ne peut faire aucune demande (ni congé, ni décision).
        if ($agent->role === 'MINISTRE') {
            return response()->json([
                'message' => 'Le Ministre ne peut pas soumettre de demande.',
            ], 403);
        }

        // Le DRH peut uniquement soumettre des demandes de congé.
        if ($agent->role === 'DRH' && $data['type'] === 'DECISION') {
            return response()->json([
                'message' => 'Le DRH ne peut pas soumettre de demande de décision.',
            ], 422);
        }

        // Seuls les AGENT_ETAT (hors DRH) peuvent soumettre une demande de décision.
        if ($data['type'] === 'DECISION' && $agent->profil !== 'AGENT_ETAT') {
            return response()->json([
                'message' => "Seuls les agents de l'État peuvent soumettre une demande de décision.",
            ], 422);
        }

        // Blocage : un AGENT_ETAT ne peut pas soumettre une nouvelle décision s'il en a déjà une active.
        if ($data['type'] === 'DECISION' && $agent->decisionActive()) {
            return response()->json([
                'message' => 'Vous avez déjà une décision en cours de validité. Attendez son expiration avant d\'en soumettre une nouvelle.',
            ], 422);
        }

        // Pour un congé : les AGENT_ETAT (sauf DRH) doivent avoir une décision active.
        $decisionActive = null;
        if ($data['type'] === 'CONGE'
            && $agent->profil === 'AGENT_ETAT'
            && $agent->role !== 'DRH'
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

        // Notifier le premier valideur du circuit
        $this->notifierValideurs($demande, 0);

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

        // Dernière demande approuvée avec référence officielle
        $derniereDemande = Demande::where('agent_id', $agent->id)
            ->where('statut', 'APPROUVEE')
            ->whereNotNull('numero_reference')
            ->where('id', '<', $demande->id)
            ->latest()
            ->first();

        $joursDepuisDerniere = $derniereDemande
            ? (int) Demande::where('agent_id', $agent->id)
                ->where('statut', 'APPROUVEE')
                ->where('id', '>', $derniereDemande->id)
                ->sum('nombre_jours')
            : 0;

        // Fusionner les champs éditables (contenu_lettre) avec les valeurs par défaut.
        $lettre = $demande->contenu_lettre ?? [];

        // Logo en base64 pour garantir le rendu DomPDF sans chemin réseau.
        $logoPath = public_path('images/dgb-logo.png');
        $logoSrc  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.demande-conge', [
            'demande'             => $demande,
            'agent'               => $agent,
            'derniereDemande'     => $derniereDemande,
            'joursDepuisDerniere' => $joursDepuisDerniere,
            'dateReprise'         => Carbon::parse($demande->date_fin)->addDay()->translatedFormat('d/m/Y'),
            'lettre'              => $lettre,
            'logoSrc'             => $logoSrc,
        ])->setPaper('a4', 'portrait');

        $ref      = $demande->numero_reference ? str_replace('/', '-', $demande->numero_reference) : 'DGB-' . $demande->id;
        $filename = "demande-{$ref}.pdf";

        return $pdf->download($filename);
    }

    // ─── PUT /api/demandes/{demande}/lettre ───────────────────────────────────
    public function updateLettre(Request $request, Demande $demande): JsonResponse
    {
        $agent = $request->user();

        // Seul le propriétaire peut modifier le contenu de sa lettre.
        if ($demande->agent_id !== $agent->id) {
            abort(403, 'Vous ne pouvez modifier que vos propres lettres.');
        }

        if ($demande->type !== 'CONGE') {
            return response()->json([
                'message' => 'La génération de lettre est réservée aux demandes de congé.',
            ], 422);
        }

        $data = $request->validate([
            'motif_lettre'    => 'nullable|string|max:2000',
            'lieu_jouissance' => 'nullable|string|max:255',
            'complement'      => 'nullable|string|max:1000',
        ]);

        $demande->update(['contenu_lettre' => $data]);

        return response()->json([
            'message'        => 'Contenu de la lettre mis à jour.',
            'contenu_lettre' => $demande->contenu_lettre,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Crée une notification VALIDATION_REQUISE pour les valideurs du niveau $index du circuit.
     */
    public static function notifierValideurs(Demande $demande, int $index): void
    {
        $demande->loadMissing('agent.direction');
        $circuit   = $demande->circuit();
        $roleReqis = $circuit[$index] ?? null;
        if (! $roleReqis) {
            return;
        }

        $query = Agent::where('role', $roleReqis);
        if ($roleReqis === 'CHEF_DIVISION') {
            $query->where('division_id', $demande->agent->division_id);
        } elseif ($roleReqis === 'DIRECTEUR') {
            $query->where('direction_id', $demande->agent->direction_id);
        }

        foreach ($query->get() as $valideur) {
            Notification::validationRequise($valideur->id, $demande, $roleReqis);
        }
    }

    private function autoriserAcces($user, Demande $demande): void
    {
        $estProprietaire = $demande->agent_id === $user->id;
        $estValideur     = \in_array($user->role, ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE'], true);

        if (! $estProprietaire && ! $estValideur) {
            abort(403, 'Accès non autorisé à cette demande.');
        }
    }
}

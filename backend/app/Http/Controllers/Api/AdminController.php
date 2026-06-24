<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isDirectionAdmin(): bool
    {
        return auth()->user()->role === 'ADMIN_DIRECTION';
    }

    private function myDirId(): ?int
    {
        return auth()->user()->direction_id;
    }

    // ── Agents ────────────────────────────────────────────────────────────────

    public function indexAgents()
    {
        $query = Agent::with(['direction', 'division'])
            ->orderBy('direction_id')
            ->orderBy('nom');

        if ($this->isDirectionAdmin()) {
            $query->where('direction_id', $this->myDirId());
        }

        return $query->get();
    }

    public function storeAgent(Request $request)
    {
        $data = $request->validate([
            'nom'          => 'required|string|max:100',
            'prenom'       => 'required|string|max:100',
            'email'        => 'required|email|unique:agents',
            'password'     => 'required|min:6',
            'direction_id' => 'nullable|exists:directions,id',
            'division_id'  => 'nullable|exists:divisions,id',
            'poste'        => 'required|string|max:150',
            'corps'        => 'nullable|string|max:100',
            'profil'       => 'required|in:CONTRACTUEL,AGENT_ETAT',
            'matricule'    => 'nullable|string|max:50|unique:agents',
            'role'         => 'required|in:AGENT,CHEF_DIVISION,DIRECTEUR,DAP,DRH,ADMIN,ADMIN_DIRECTION,DGB,MINISTRE',
        ]);

        // ADMIN_DIRECTION : force sa propre direction, interdit les rôles globaux
        if ($this->isDirectionAdmin()) {
            $data['direction_id'] = $this->myDirId();
            if (in_array($data['role'], ['ADMIN', 'ADMIN_DIRECTION', 'DAP', 'DRH', 'DGB', 'MINISTRE'])) {
                return response()->json(['message' => 'Vous ne pouvez pas attribuer ce rôle.'], 403);
            }
        }

        // Règles direction/division par rôle :
        // DIRECTEUR, DAP          : ont une direction, mais pas de division
        // DRH, DGB, MINISTRE, ADMIN : ni direction ni division
        $rolesGlobaux   = ['ADMIN', 'DRH', 'DGB', 'MINISTRE'];
        $rolesSansDiv   = ['ADMIN', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE'];

        if (!in_array($data['role'], $rolesGlobaux) && empty($data['direction_id'])) {
            return response()->json(['message' => 'La direction est obligatoire pour ce rôle.'], 422);
        }
        if (!in_array($data['role'], $rolesSansDiv) && empty($data['division_id'])) {
            return response()->json(['message' => 'La division est obligatoire pour ce rôle.'], 422);
        }
        if (in_array($data['role'], ['DIRECTEUR', 'DAP'])) {
            $data['division_id'] = null;
        }
        if (in_array($data['role'], ['DRH', 'DGB', 'MINISTRE', 'ADMIN'])) {
            $data['direction_id'] = null;
            $data['division_id']  = null;
        }

        $data['password']             = Hash::make($data['password']);
        $data['must_change_password'] = true;

        return response()->json(
            Agent::create($data)->load(['direction', 'division']),
            201
        );
    }

    public function updateAgent(Request $request, Agent $agent)
    {
        // ADMIN_DIRECTION ne peut modifier que les agents de sa direction
        if ($this->isDirectionAdmin() && $agent->direction_id !== $this->myDirId()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $data = $request->validate([
            'nom'          => 'sometimes|required|string|max:100',
            'prenom'       => 'sometimes|required|string|max:100',
            'email'        => "sometimes|required|email|unique:agents,email,{$agent->id}",
            'password'     => 'nullable|min:6',
            'direction_id' => 'nullable|exists:directions,id',
            'division_id'  => 'nullable|exists:divisions,id',
            'poste'        => 'sometimes|required|string|max:150',
            'corps'        => 'nullable|string|max:100',
            'profil'       => 'sometimes|required|in:CONTRACTUEL,AGENT_ETAT',
            'matricule'    => "nullable|string|max:50|unique:agents,matricule,{$agent->id}",
            'role'         => 'sometimes|required|in:AGENT,CHEF_DIVISION,DIRECTEUR,DAP,DRH,ADMIN,ADMIN_DIRECTION,DGB,MINISTRE',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $agent->update($data);

        return response()->json($agent->fresh(['direction', 'division']));
    }

    public function destroyAgent(Agent $agent)
    {
        if (auth()->id() === $agent->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte.'], 403);
        }

        if ($this->isDirectionAdmin() && $agent->direction_id !== $this->myDirId()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $agent->delete();

        return response()->json(['message' => 'Agent supprimé.']);
    }

    // ── Tableau de bord global ─────────────────────────────────────────────────

    public function dashboard()
    {
        $dirId = $this->isDirectionAdmin() ? $this->myDirId() : null;

        $agentQuery   = Agent::where('role', 'not in', ['ADMIN', 'ADMIN_DIRECTION']);
        $demandeQuery = Demande::query();

        if ($dirId) {
            $agentQuery->where('direction_id', $dirId);
            $agentIds = Agent::where('direction_id', $dirId)->pluck('id');
            $demandeQuery->whereIn('agent_id', $agentIds);
        }

        $recentes = (clone $demandeQuery)
            ->with(['agent.direction', 'agent.division'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json([
            'total_agents'   => $agentQuery->count(),
            'total_demandes' => (clone $demandeQuery)->count(),
            'en_attente'     => (clone $demandeQuery)->where('statut', 'EN_ATTENTE')->count(),
            'approuvees'     => (clone $demandeQuery)->where('statut', 'APPROUVEE')->count(),
            'rejetees'       => (clone $demandeQuery)->where('statut', 'REJETEE')->count(),
            'conges'         => (clone $demandeQuery)->where('type', 'CONGE')->count(),
            'decisions'      => (clone $demandeQuery)->where('type', 'DECISION')->count(),
            'recentes'       => $recentes,
        ]);
    }

    // ── Toutes les demandes ────────────────────────────────────────────────────

    public function indexDemandes(Request $request)
    {
        $query = Demande::with(['agent.direction', 'agent.division'])
                        ->orderByDesc('created_at');

        if ($this->isDirectionAdmin()) {
            $agentIds = Agent::where('direction_id', $this->myDirId())->pluck('id');
            $query->whereIn('agent_id', $agentIds);
        }

        if ($request->input('statut')) $query->where('statut', $request->input('statut'));
        if ($request->input('type'))   $query->where('type',   $request->input('type'));
        if ($request->input('annee'))  $query->where('annee',  $request->input('annee'));

        return $query->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Demande extends Model
{
    protected $fillable = [
        'agent_id', 'type',
        'date_debut', 'date_fin', 'nombre_jours',
        'motif', 'lieu_jouissance',
        'statut', 'niveau_courant', 'annee',
        'numero_reference', 'decision_reference_id',
        'date_validation', 'duree_jours',
    ];

    protected function casts(): array
    {
        return [
            'date_debut'      => 'date',
            'date_fin'        => 'date',
            'date_validation' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(Validation::class)->orderBy('niveau');
    }

    public function decisionReference(): BelongsTo
    {
        return $this->belongsTo(Demande::class, 'decision_reference_id');
    }

    /**
     * Circuit de validation selon le rôle du demandeur et le type de demande.
     *
     * Tableau ordonné des rôles requis à chaque niveau (index 0 = niveau 1).
     *
     * | Demandeur     | Type     | Circuit                               |
     * |---------------|----------|---------------------------------------|
     * | DGB           | any      | [MINISTRE]                            |
     * | DIRECTEUR     | CONGE    | [DGB]                                 |
     * | DIRECTEUR     | DECISION | [DGB, DRH]                            |
     * | CHEF_DIVISION | CONGE    | [DIRECTEUR]                           |
     * | CHEF_DIVISION | DECISION | [DIRECTEUR, DAP, DRH]                 |
     * | AGENT         | DECISION | [CHEF_DIVISION, DIRECTEUR, DAP, DRH]  |
     * | AGENT         | CONGE    | [CHEF_DIVISION, DIRECTEUR]            |
     *
     * Requires $this->agent to be loaded (lazy-loaded if not already).
     */
    public function circuit(): array
    {
        $role = $this->agent->role;
        $type = $this->type;

        return match (true) {
            $role === 'DGB'                                   => ['MINISTRE'],
            $role === 'DIRECTEUR' && $type === 'CONGE'        => ['DGB'],
            $role === 'DIRECTEUR' && $type === 'DECISION'     => ['DGB', 'DRH'],
            $role === 'CHEF_DIVISION' && $type === 'CONGE'    => ['DIRECTEUR'],
            $role === 'CHEF_DIVISION' && $type === 'DECISION' => ['DIRECTEUR', 'DAP', 'DRH'],
            $type === 'DECISION'                              => ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH'],
            default                                           => ['CHEF_DIVISION', 'DIRECTEUR'],
        };
    }

    public function niveauxMax(): int
    {
        return count($this->circuit());
    }

    public function estTerminee(): bool
    {
        return in_array($this->statut, ['APPROUVEE', 'REJETEE']);
    }

    /**
     * Génère la référence officielle au moment de l'approbation finale.
     * Format : DGB/[SIGLE_DIR]/[ANNEE]/[SEQ 3 chiffres]
     */
    public static function genererReference(Agent $agent): string
    {
        $sigle = match ($agent->role) {
            'DGB'      => 'DGB',
            'MINISTRE' => 'MIN',
            default    => $agent->direction?->sigle ?? 'DGB',
        };

        $annee = now()->year;
        $seq   = static::whereYear('created_at', $annee)
                       ->whereNotNull('numero_reference')
                       ->count() + 1;

        return \sprintf('DGB/%s/%d/%03d', $sigle, $annee, $seq);
    }
}

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
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin'   => 'date',
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

    public function niveauxMax(): int
    {
        return $this->type === 'DECISION' ? 4 : 2;
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
        $sigle  = $agent->direction->sigle;
        $annee  = now()->year;
        $seq    = static::whereYear('created_at', $annee)
                        ->whereNotNull('numero_reference')
                        ->count() + 1;

        return sprintf('DGB/%s/%d/%03d', $sigle, $annee, $seq);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'agents';

    protected $fillable = [
        'nom', 'prenom', 'email', 'password',
        'direction_id', 'division_id',
        'poste', 'corps',
        'profil', 'matricule', 'telephone', 'role', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class, 'agent_id');
    }

    public function validationsEffectuees(): HasMany
    {
        return $this->hasMany(Validation::class, 'valideur_id');
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function soldeJours(): int
    {
        $anneeCreation = $this->created_at?->year ?? now()->year;
        $anneesService = max(1, now()->year - $anneeCreation + 1);
        $droits = min(30 * $anneesService, 90);

        // Seuls les congés approuvés consomment le solde ; les décisions en sont exclues
        $joursConsommes = (int) $this->demandes()
            ->where('statut', 'APPROUVEE')
            ->where('type', 'CONGE')
            ->sum('nombre_jours');

        return max(0, $droits - $joursConsommes);
    }

    /**
     * Retourne la décision active : DECISION approuvée dont la date d'expiration
     * (date_validation + duree_jours) est dans le futur.
     * COALESCE(duree_jours, 180) assure la rétrocompatibilité avec les décisions sans durée.
     * La comparaison est effectuée en PHP pour rester compatible SQLite et MySQL.
     */
    public function decisionActive(): ?Demande
    {
        return $this->demandes()
            ->where('type', 'DECISION')
            ->where('statut', 'APPROUVEE')
            ->whereNotNull('date_validation')
            ->get()
            ->filter(fn(Demande $d) => $d->date_validation
                ->copy()
                ->addDays($d->duree_jours ?? 180)
                ->isFuture()
            )
            ->sortByDesc('date_validation')
            ->first();
    }

    public function peutSoumettreConge(): bool
    {
        if ($this->profil === 'CONTRACTUEL') {
            return true;
        }
        // DRH : peut faire un congé sans décision active (il ne fait jamais de décision).
        if ($this->role === 'DRH') {
            return true;
        }
        // Tous les autres AGENT_ETAT (AGENT, CHEF_DIVISION, DIRECTEUR, DGB) ont besoin d'une décision active.
        return $this->decisionActive() !== null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'agents';

    protected $fillable = [
        'nom', 'prenom', 'email', 'password',
        'direction_id', 'division_id',
        'poste', 'corps',
        'profil', 'matricule', 'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

        $joursConsommes = (int) $this->demandes()
            ->where('statut', 'APPROUVEE')
            ->sum('nombre_jours');

        return max(0, $droits - $joursConsommes);
    }

    /**
     * Retourne la décision active (APPROUVEE, type DECISION, dont la période couvre aujourd'hui).
     */
    public function decisionActive(): ?Demande
    {
        return $this->demandes()
            ->where('type', 'DECISION')
            ->where('statut', 'APPROUVEE')
            ->where('date_debut', '<=', now()->toDateString())
            ->where('date_fin', '>=', now()->toDateString())
            ->latest()
            ->first();
    }

    public function peutSoumettreConge(): bool
    {
        if ($this->profil === 'CONTRACTUEL') {
            return true;
        }
        // AGENT_ETAT : congé possible seulement pendant une décision active
        return $this->decisionActive() !== null;
    }
}

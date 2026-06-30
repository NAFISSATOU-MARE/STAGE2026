<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'agent_id', 'type', 'demande_id', 'message', 'lu',
    ];

    protected function casts(): array
    {
        return ['lu' => 'boolean'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class);
    }

    // ── Helpers de création ────────────────────────────────────────────────

    public static function validationRequise(int $agentId, Demande $demande, string $roleValideur): void
    {
        static::create([
            'agent_id'   => $agentId,
            'type'       => 'VALIDATION_REQUISE',
            'demande_id' => $demande->id,
            'message'    => "Une demande de {$demande->type} attend votre validation (Ref. #{$demande->id}).",
        ]);
    }

    public static function validationRecue(Demande $demande): void
    {
        static::create([
            'agent_id'   => $demande->agent_id,
            'type'       => 'VALIDATION_RECUE',
            'demande_id' => $demande->id,
            'message'    => "Votre demande de {$demande->type} a été approuvée (Réf. {$demande->numero_reference}).",
        ]);
    }

    public static function rejetRecu(Demande $demande, ?string $motif = null): void
    {
        $msg = "Votre demande de {$demande->type} a été rejetée.";
        if ($motif) {
            $msg .= " Motif : {$motif}";
        }
        static::create([
            'agent_id'   => $demande->agent_id,
            'type'       => 'REJET_RECU',
            'demande_id' => $demande->id,
            'message'    => $msg,
        ]);
    }

    public static function compteCree(Agent $agent): void
    {
        static::create([
            'agent_id'   => $agent->id,
            'type'       => 'COMPTE_CREE',
            'demande_id' => null,
            'message'    => "Bienvenue ! Votre compte a été créé. Votre mot de passe vous a été envoyé par email.",
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validation extends Model
{
    protected $fillable = [
        'demande_id', 'valideur_id',
        'niveau', 'avis', 'motif_refus',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class);
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'valideur_id');
    }
}

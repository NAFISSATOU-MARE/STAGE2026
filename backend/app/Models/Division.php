<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Division extends Model
{
    protected $fillable = ['direction_id', 'sigle', 'nom'];

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function chefDivision(): HasOne
    {
        return $this->hasOne(Agent::class)->where('role', 'CHEF_DIVISION');
    }
}

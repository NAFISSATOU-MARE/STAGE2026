<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    protected $fillable = ['sigle', 'nom'];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}

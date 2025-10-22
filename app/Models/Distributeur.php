<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distributeur extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'super_agent_id',
    ];

    public function superAgent()
    {
        return $this->belongsTo(Super_agent::class, 'super_agent_id');
    }

    public function kiosques()
    {
        return $this->hasMany(Kiosque::class, 'distributeur_id');
    }
}

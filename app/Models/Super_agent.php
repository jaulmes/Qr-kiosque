<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Super_agent extends Model
{
    protected $fillable = [
        'name',
        'region'
    ];

    public function distributeurs()
    {
        return $this->hasMany(Distributeur::class, 'super_agent_id');
    }
}

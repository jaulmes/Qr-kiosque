<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kiosque extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phone',
        'bv',
        'region',       
        'distributeur_id',

    ];

    public function distributeur()
    {
        return $this->belongsTo(Distributeur::class, 'distributeur_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProgress extends Model
{
    protected $fillable = [
        'job_id', 'status', 'progress', 'message'
    ];
    protected $casts = [
        'progress' => 'integer',
    ];
}

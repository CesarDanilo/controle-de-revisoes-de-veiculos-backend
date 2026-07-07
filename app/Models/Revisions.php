<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revisions extends Model
{
    protected $fillable = [
        'vehicle_id',
        'description',
        'revision_date',
        'cost',
        'next_revision_date',
        'next_revision_km',
        'km',
        'user_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicle';

    protected $fillable = [
        'user_id',
        'model',
        'year',
        'color',
        'brand_id',
        'people_id',
        'license_plate',
    ];
}


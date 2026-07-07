<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{

    use HasUuids;
    
    protected $table = 'vehicle';

    protected $keyType = 'string';
    public $incrementing = false;

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


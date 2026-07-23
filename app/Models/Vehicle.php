<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{

    use HasUuidPrimaryKey;
    use HasFactory;
    
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


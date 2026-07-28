<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\PodeSerMovidoParaLixeira;

class Vehicle extends Model
{

    use HasUuidPrimaryKey;
    use HasFactory;
    use PodeSerMovidoParaLixeira;
    
    protected $table = 'vehicle';

    protected $fillable = [
        'user_id',
        'model',
        'year',
        'color_id',
        'brand_id',
        'people_id',
        'license_plate',
    ];
}


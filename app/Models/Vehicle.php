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

    // 🟢 NOVO — usado pelo Revisions::with('vehicle.people') no
    // RevisionsController@index, pra trazer o dono do veículo junto
    // de cada revisão sem N+1 query.
    public function people()
    {
        return $this->belongsTo(People::class, 'people_id');
    }
}
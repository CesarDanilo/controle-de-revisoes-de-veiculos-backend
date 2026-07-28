<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\PodeSerMovidoParaLixeira;

class People extends Model
{
    use HasUuidPrimaryKey;
    use HasFactory;
    use PodeSerMovidoParaLixeira;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'document',
        'phone',
        'birth_date',
        'gender',
    ];
}
<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'brands';
    
    protected $fillable = [
        'user_id',
        'name',
    ];
}

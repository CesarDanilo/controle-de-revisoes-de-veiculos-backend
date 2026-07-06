<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $table = 'brands';

    protected $fillable = [
        'user_id',
        'name',
    ];
}

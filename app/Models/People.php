<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class People extends Model
{
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
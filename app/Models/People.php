<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasUuids;

    protected $table = 'people';

    protected $keyType = 'string';
    public $incrementing = false;

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
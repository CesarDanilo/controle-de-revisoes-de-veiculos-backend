<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasUuidPrimaryKey;

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
<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class People extends Model
{
    use HasUuidPrimaryKey;
    use HasFactory;

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
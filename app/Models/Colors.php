<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colors extends Model
{
    use HasUuidPrimaryKey;
    use HasFactory;

    protected $table = 'colors';

    protected $fillable = [
        'name',
    ];
}
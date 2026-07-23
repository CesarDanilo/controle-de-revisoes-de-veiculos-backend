<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Revisions extends Model
{
    use HasUuidPrimaryKey;
    use HasFactory;
    
    protected $fillable = [
        'vehicle_id',
        'description',
        'revision_date',
        'cost',
        'next_revision_date',
        'next_revision_km',
        'km',
        'user_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    use HasFactory, HasUuids; // ✅ Usa a trait nativa de UUID do Laravel

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'user_id',
    ];
}
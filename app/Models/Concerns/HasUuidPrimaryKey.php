<?php

namespace App\Models\Concerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasUuidPrimaryKey
{
    // REMOVIDO: protected $keyType = 'string';

    public function initializeHasUuidPrimaryKey(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
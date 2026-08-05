<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'report_exports';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'params',
        'params_hash',
        'data_version',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
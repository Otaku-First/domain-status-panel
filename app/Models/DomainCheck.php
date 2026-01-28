<?php

namespace App\Models;

use App\Enums\CheckResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainCheck extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'domain_id',
        'result',
        'response_code',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'result' => CheckResult::class,
        'checked_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
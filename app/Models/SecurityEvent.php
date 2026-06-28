<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'type',
        'level',
        'description',
        'source_ip',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginFailure extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_ip',
        'email',
        'user_agent',
    ];
}

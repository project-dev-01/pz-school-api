<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log_history extends Model
{
    use HasFactory;
    protected $table = 'log_history';

    protected $fillable = [
        'logout_time', // Add logout_time to the fillable attributes
        // Other fillable attributes...
    ];
}

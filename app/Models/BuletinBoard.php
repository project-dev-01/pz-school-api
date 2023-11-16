<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuletinBoard extends Model
{
    use HasFactory;
    protected $fillable = [
        'title','discription','file','target_user','publish_date','publish_time','status'
    ];
}

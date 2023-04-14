<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branches extends Model
{
    use HasFactory;
    protected $table = 'branches';

    protected $fillable = [
        'name',
        'school_name',
        'branch_code',
        'email',
        'mobile_no',
        'currency',
        'symbol',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'db_name',
        'logo',
        'db_username',
        'db_password',
    ];
}

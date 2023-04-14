<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;
    protected $table = 'staffs';

    protected $fillable = [
        'staff_id',
        'name',
        'department',
        'qualification',
        'designation',
        'joining_date',
        'birthday',
        'gender',
        'religion',
        'blood_group',
        'present_address',
        'permanent_address',
        'mobile_no',
        'email',
        'branch_id',
        'photo',
        'facebook_url',
        'linkedin_url',
        'twitter_url'
    ];
}

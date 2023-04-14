<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum_count_details extends Model
{
    use HasFactory;
    protected $table = 'forum_count_details';

    protected $fillable = [
        'branch_id',
        'user_id',
        'user_name',
        'created_post_id',
        'likes',
        'dislikes' ,
        'favorite',
        'replies',
        'views',
        'activity'        
    ];
}
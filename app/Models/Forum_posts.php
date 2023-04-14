<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum_posts extends Model
{
    use HasFactory;
    protected $table = 'forum_posts';

    protected $fillable = [
        'branch_id',
        'user_id',
        'user_name',
        'topic_title',
        'topic_header',
        'types' ,
        'body_content',
        'category',
        'tags',
        'imagesorvideos',
        'threads_status',
        'token'         
    ];
}
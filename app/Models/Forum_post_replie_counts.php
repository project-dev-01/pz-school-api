<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum_post_replie_counts extends Model
{
    use HasFactory;
    protected $table = 'forum_post_replie_counts';

    protected $fillable = [
        'branch_id',
        'user_id',
        'user_name',
        'created_post_id',
        'created_post_replies_id',
        'likes',
        'dislikes',
        'favorits',
        'flag'
    ];
}
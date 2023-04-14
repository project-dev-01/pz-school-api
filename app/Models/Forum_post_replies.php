<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum_post_replies extends Model
{
    use HasFactory;
    protected $table = 'forum_post_replies';

    protected $fillable = [
        'branch_id',
        'user_id',
        'user_name',
        'created_post_id',
        'replies_com'       
    ];
}
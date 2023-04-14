<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $fillable = [
        'role_name',
        'role_slug',
        'status'
    ];

    public function users() {
        return $this->HasMany(User::class);
    }
}

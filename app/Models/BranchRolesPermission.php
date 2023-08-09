<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRolesPermission extends Model
{
    use HasFactory;
    protected $table = 'branch_roles_permissions';
    protected $fillable = [
        'branch_id',
        'role_id',
        'permission_status'
    ];
}

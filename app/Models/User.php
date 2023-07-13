<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'branch_id',
        'role_id',
        'picture',
        'email',
        'password',
        'status',
        'login_attempt',
        'session_id',
        'email_verified_at',
        'password_changed_at',
        'google2fa_secret',
        'google2fa_secret_enable',
        'last_seen'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function subsDetails()
    {
        return $this->belongsTo(Branches::class, 'branch_id');
    }
    // 2fa ecrypt and dycrypt
    public function setGoogle2faSecretAttribute($value)
    {
        // dd($value);
        // $this->attributes['google2fa_secret'] = encrypt($value);
        return encrypt($value);
    }

    public function getGoogle2faSecretAttribute($value)
    {
        if($value === null || trim($value) === ''){
            return null;
        }else{
            return decrypt($value);
        }
    }
}

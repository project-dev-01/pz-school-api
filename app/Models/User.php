<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;

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
        'school_roleid',
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
        'is_active',
        'last_seen'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    // public function getPasswordAttribute($inputPassword) 
    // { 
    //     return $this->password;

    // }
    public function toArray()
    {
        $array = parent::toArray();
        // $array['user'] = [
        //     'id' => $this->id,
        //     'name' => $this->name,
        //     'user_id' => $this->user_id,
        //     'branch_id' => $this->branch_id,
        //     'role_id' => $this->role_id,
        //     'school_roleid' => $this->school_roleid,
        //     'email' => $this->email,
        //     'picture' => $this->picture,
        //     'status' => $this->status,
        //     'login_attempt' => $this->login_attempt,
        //     'password' => $this->password,
        //     'session_id' => $this->session_id,
        //     'google2fa_secret' => $this->google2fa_secret,
        //     'google2fa_secret_enable' => $this->google2fa_secret_enable,
        //     'is_active' => $this->is_active,
        //     'last_seen' => $this->last_seen,
        //     // Add other fields you want to include from the user
        // ];
        // Include only selected fields from the 'subsDetails' relationship
        $array['subs_details'] = [
            'id' => $this->subs_details->id,
            'school_name' => $this->subs_details->school_name,
            'school_code' => $this->subs_details->school_code,
            'logo' => $this->subs_details->logo
            // Add other fields you want to include
        ];
        // $array['subsDetails'] = [
        //     'id' => $this->subsDetails->id,
        //     'school_name' => $this->subsDetails->school_name,
        //     'school_code' => $this->subsDetails->school_code,
        //     'logo' => $this->subsDetails->logo
        //     // Add other fields you want to include
        // ];
        // Include selected fields from the 'role' relationship
        $array['role'] = [
            'id' => $this->role->id,
            'role_name' => $this->role->role_name,
            // Add other fields you want to include from 'role'
        ];
        return $array;
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function subs_details()
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
        if ($value === null || trim($value) === '') {
            return null;
        } else {
            return decrypt($value);
        }
    }
}

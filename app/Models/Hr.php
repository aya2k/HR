<?php

namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Model;

class Hr extends Authenticatable implements JWTSubject
{
    protected $fillable = ['name','email','password'];
    protected $hidden = ['password'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

     public function roles()
    {
        return $this->belongsToMany(Role::class, 'hr_role');
    }

    public function permissions()
    {
        return $this->roles->flatMap->permissions->pluck('name')->unique();
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions()->toArray());
    }

    public function isSuperAdmin()
{
    return (bool) $this->is_super_admin;
}

}
